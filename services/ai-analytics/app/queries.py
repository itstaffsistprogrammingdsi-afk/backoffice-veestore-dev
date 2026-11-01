SALES_DAILY_SQL = """
WITH paid_store_visits AS (
    SELECT svp.visit_id, MAX(COALESCE(p.completed_at, p.created_at)) AS completed_at
    FROM store_visit_payments svp
    JOIN payments p ON p.id = svp.payment_id
    WHERE p.status = 'paid'
    GROUP BY svp.visit_id
), store_sales AS (
    SELECT DATE(pv.completed_at AT TIME ZONE :business_timezone) AS sale_date,
           'store'::text AS channel, sc.store_id, NULL::uuid AS warehouse_id,
           i.product_id, COUNT(scd.id)::numeric AS units,
           SUM(GREATEST(COALESCE(scd.product_price_amount, 0) - COALESCE(scd.discount_amount, 0), 0))::numeric AS revenue
    FROM store_carts sc
    JOIN store_customer_visits v ON v.id = sc.visit_id
    JOIN store_cart_details scd ON scd.cart_id = sc.id
    JOIN items i ON i.id = scd.item_id
    JOIN paid_store_visits pv ON pv.visit_id = v.id
    WHERE sc.status = 'paid' AND v.status = 'completed' AND v.payment_status = 'paid'
      AND pv.completed_at >= :from_ts AND pv.completed_at < :to_ts
      AND scd.void_transaction_id IS NULL
      AND COALESCE(scd.void_status, '') NOT IN ('voided', 'approved', 'completed')
    GROUP BY 1, 3, 4, 5
), ecommerce_sales AS (
    SELECT DATE(COALESCE(p.completed_at, eo.created_at) AT TIME ZONE :business_timezone) AS sale_date,
           'ecommerce'::text AS channel, eo.handled_by_store_id AS store_id,
           eo.handled_by_warehouse_id AS warehouse_id, eod.product_id,
           SUM(COALESCE(eod.quantity, 0))::numeric AS units,
           SUM(COALESCE(eod.total_price,
               GREATEST(COALESCE(eod.product_price, 0) * COALESCE(eod.quantity, 0) - COALESCE(eod.total_discount, 0), 0)
           ))::numeric AS revenue
    FROM ecommerce_orders eo
    JOIN ecommerce_order_details eod ON eod.ecommerce_order_id = eo.id
    JOIN payments p ON p.id = eo.payment_id
    WHERE eo.status IN ('paid', 'processed', 'packing', 'shipped', 'received')
      AND p.status = 'paid' AND COALESCE(p.completed_at, eo.created_at) >= :from_ts
      AND COALESCE(p.completed_at, eo.created_at) < :to_ts
    GROUP BY 1, 3, 4, 5
)
SELECT * FROM store_sales UNION ALL SELECT * FROM ecommerce_sales
ORDER BY sale_date, channel, store_id, warehouse_id, product_id
"""

STORE_STOCK_SQL = """
SELECT si.store_id, i.product_id,
       COUNT(*) FILTER (WHERE i.status IN ('store_stock', 'store_display'))::int AS current_stock,
       COUNT(*) FILTER (WHERE i.status IN ('reserved', 'in_transaction'))::int AS reserved_stock,
       COALESCE(ssb.stock_barrier, 0)::int AS stock_barrier
FROM store_items si
JOIN items i ON i.id = si.item_id
LEFT JOIN store_stock_barriers ssb ON ssb.store_id = si.store_id AND ssb.product_id = i.product_id
GROUP BY si.store_id, i.product_id, ssb.stock_barrier
"""
