from __future__ import annotations

from collections import defaultdict
from datetime import date, datetime, time, timedelta, timezone
from uuid import UUID, uuid4
from zoneinfo import ZoneInfo

from sqlalchemy import text
from sqlalchemy.engine import Engine

from app.config import Settings
from app.forecasting import moving_average_forecast, restock_quantity, stockout_risk
from app.queries import SALES_DAILY_SQL, STORE_STOCK_SQL


def run_forecast_pipeline(source_engine: Engine, analytics_engine: Engine, settings: Settings) -> UUID:
    run_id = uuid4()
    business_tz = ZoneInfo(settings.business_timezone)
    now = datetime.now(business_tz)
    training_to = now.date() - timedelta(days=1)
    training_from = training_to - timedelta(days=settings.forecast_lookback_days - 1)

    with analytics_engine.begin() as conn:
        conn.execute(text("""
            INSERT INTO analytics_model_runs
                (id, model_name, model_type, model_version, status, training_from, training_to,
                 parameters, started_at, created_at, updated_at)
            VALUES (:id, 'sales-moving-average', 'forecast', '1.0.0', 'running', :training_from,
                    :training_to, CAST(:parameters AS jsonb), :now, :now, :now)
        """), {
            "id": run_id, "training_from": training_from, "training_to": training_to,
            "parameters": '{"window_days":28,"trend_days":7}', "now": now,
        })

    try:
        with source_engine.connect() as conn:
            sales = conn.execute(text(SALES_DAILY_SQL), {
                "from_ts": datetime.combine(training_from, time.min, tzinfo=business_tz),
                "to_ts": datetime.combine(training_to + timedelta(days=1), time.min, tzinfo=business_tz),
                "business_timezone": settings.business_timezone,
            }).mappings().all()
            stocks = conn.execute(text(STORE_STOCK_SQL)).mappings().all()

        grouped: dict[tuple, dict[date, float]] = defaultdict(dict)
        revenue_per_unit: dict[tuple, list[float]] = defaultdict(list)
        for row in sales:
            key = (row["channel"], row["store_id"], row["warehouse_id"], row["product_id"])
            grouped[key][row["sale_date"]] = float(row["units"] or 0)
            if row["units"] and row["revenue"]:
                revenue_per_unit[key].append(float(row["revenue"]) / float(row["units"]))

        forecast_rows: list[dict] = []
        forecast_7d: dict[tuple, float] = defaultdict(float)
        total_series = len(grouped)
        eligible_series = 0
        for key, observations in grouped.items():
            sales_days = sum(1 for quantity in observations.values() if quantity > 0)
            # Seri harus meliputi seluruh periode training. Tanpa titik awal ini,
            # satu transaksi terbaru keliru dianggap sebagai penjualan harian.
            observations.setdefault(training_from, 0.0)
            observations.setdefault(training_to, 0.0)
            if (max(observations) - min(observations)).days + 1 < settings.min_history_days:
                continue
            if sales_days < settings.min_sales_days:
                continue
            eligible_series += 1
            unit_prices = revenue_per_unit[key]
            avg_price = sum(unit_prices) / len(unit_prices) if unit_prices else 0.0
            for point in moving_average_forecast(observations, settings.forecast_horizon_days):
                forecast_7d[key] += point.predicted_qty
                forecast_rows.append({
                    "id": uuid4(), "model_run_id": run_id, "forecast_date": point.forecast_date,
                    "channel": key[0], "store_id": key[1], "warehouse_id": key[2],
                    "product_id": key[3], "horizon_days": settings.forecast_horizon_days,
                    "predicted_qty": point.predicted_qty, "lower_qty": point.lower_qty,
                    "upper_qty": point.upper_qty, "predicted_revenue": point.predicted_qty * avg_price,
                    "confidence": point.confidence, "generated_at": now, "created_at": now, "updated_at": now,
                })

        stock_rows = {(r["store_id"], r["product_id"]): r for r in stocks}
        recommendation_rows: list[dict] = []
        for key, forecast_qty in forecast_7d.items():
            channel, store_id, warehouse_id, product_id = key
            if channel != "store" or store_id is None:
                continue
            stock = stock_rows.get((store_id, product_id), {})
            current = int(stock.get("current_stock", 0))
            reserved = int(stock.get("reserved_stock", 0))
            barrier = int(stock.get("stock_barrier", 0))
            safety = max(barrier, round(forecast_qty * 0.25))
            reorder = restock_quantity(forecast_qty, current, reserved, safety)
            recommendation_rows.append({
                "id": uuid4(), "model_run_id": run_id, "store_id": store_id, "product_id": product_id,
                "current_stock": current, "reserved_stock": reserved, "forecast_qty_7d": forecast_qty,
                "average_daily_demand": forecast_qty / max(1, settings.forecast_horizon_days),
                "safety_stock": safety, "recommended_qty": reorder,
                "risk_level": stockout_risk(reorder, forecast_qty, max(0, current - reserved)),
                "status": "recommended", "generated_at": now, "created_at": now, "updated_at": now,
            })

        with analytics_engine.begin() as conn:
            if forecast_rows:
                conn.execute(text("""
                    INSERT INTO analytics_sales_forecasts
                        (id, model_run_id, forecast_date, channel, store_id, warehouse_id, product_id,
                         horizon_days, predicted_qty, lower_qty, upper_qty, predicted_revenue, confidence,
                         generated_at, created_at, updated_at)
                    VALUES (:id, :model_run_id, :forecast_date, :channel, :store_id, :warehouse_id,
                            :product_id, :horizon_days, :predicted_qty, :lower_qty, :upper_qty,
                            :predicted_revenue, :confidence, :generated_at, :created_at, :updated_at)
                """), forecast_rows)
            if recommendation_rows:
                conn.execute(text("""
                    INSERT INTO analytics_stock_recommendations
                        (id, model_run_id, store_id, product_id, current_stock, reserved_stock,
                         forecast_qty_7d, average_daily_demand, safety_stock, recommended_qty,
                         risk_level, status, generated_at, created_at, updated_at)
                    VALUES (:id, :model_run_id, :store_id, :product_id, :current_stock, :reserved_stock,
                            :forecast_qty_7d, :average_daily_demand, :safety_stock, :recommended_qty,
                            :risk_level, :status, :generated_at, :created_at, :updated_at)
                """), recommendation_rows)
            conn.execute(text("""
                UPDATE analytics_model_runs SET status='completed', completed_at=:now,
                    metrics=CAST(:metrics AS jsonb), updated_at=:now WHERE id=:id
            """), {
                "id": run_id, "now": now,
                "metrics": (
                    '{"forecast_rows":%d,"recommendation_rows":%d,'
                    '"total_series":%d,"eligible_series":%d,"skipped_series":%d,'
                    '"minimum_sales_days":%d}'
                ) % (
                    len(forecast_rows), len(recommendation_rows), total_series,
                    eligible_series, total_series - eligible_series, settings.min_sales_days,
                ),
            })
        return run_id
    except Exception as exc:
        with analytics_engine.begin() as conn:
            conn.execute(text("""
                UPDATE analytics_model_runs SET status='failed', error_message=:error,
                    completed_at=:now, updated_at=:now WHERE id=:id
            """), {"id": run_id, "error": str(exc)[:2000], "now": datetime.now(timezone.utc)})
        raise
