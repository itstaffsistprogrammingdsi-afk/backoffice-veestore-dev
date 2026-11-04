export interface StoreTransferRow {
    id: string;
    store_order_id: string;
    order_number: string;
    store_id: string;
    store_name: string;
    store_code: string | null;
    warehouse_id: string;
    warehouse_name: string;
    warehouse_code: string | null;
    invoice_number: string | null;
    delivery_order_number: string | null;
    shipping_number: string | null;
    store_inbound_id: string | null;
    released_at: string | null;
    shipment_date: string | null;
    received_at: string | null;
    outbound_qty: number;
    received_qty: number;
    in_delivery_qty: number;
    outstanding_qty: number;
    age_days: number;
    is_overdue: boolean;
    status: 'not_recorded' | 'partial' | 'recorded';
    status_label: string;
    event_date: string | null;
}

export interface StoreTransferReconciliation {
    summary: {
        total_transfers: number;
        not_recorded_count: number;
        partial_count: number;
        recorded_count: number;
        unresolved_count: number;
        affected_store_count: number;
        outstanding_qty: number;
        oldest_days: number;
        overdue_count: number;
    };
    rows: StoreTransferRow[];
    stores: Array<{
        store_id: string;
        store_name: string;
        store_code: string | null;
        transfer_count: number;
        not_recorded_count: number;
        outstanding_qty: number;
        oldest_days: number;
    }>;
    statusOptions: Array<{ value: string; label: string }>;
}
