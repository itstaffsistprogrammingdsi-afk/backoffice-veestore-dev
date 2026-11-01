from __future__ import annotations

from dataclasses import dataclass
from datetime import date, timedelta
from math import sqrt
from statistics import mean, pstdev


@dataclass(frozen=True)
class ForecastPoint:
    forecast_date: date
    predicted_qty: float
    lower_qty: float
    upper_qty: float
    confidence: float


def moving_average_forecast(
    observations: dict[date, float],
    horizon_days: int = 7,
    window_days: int = 28,
) -> list[ForecastPoint]:
    """Forecast transparan: moving average dengan tren yang dibatasi dan interval 95%."""
    if not observations:
        return []

    last_date = max(observations)
    first_date = min(observations)
    values: list[float] = []
    cursor = first_date
    while cursor <= last_date:
        values.append(max(0.0, float(observations.get(cursor, 0.0))))
        cursor += timedelta(days=1)

    recent = values[-window_days:]
    base = mean(recent)
    trend = 0.0
    if len(recent) >= 14:
        previous = mean(recent[-14:-7])
        current = mean(recent[-7:])
        trend = max(-base * 0.25, min(base * 0.25, (current - previous) / 7))

    deviation = pstdev(recent) if len(recent) > 1 else 0.0
    history_factor = min(1.0, len(values) / 90)
    volatility_factor = 1.0 / (1.0 + (deviation / base if base > 0 else deviation))
    confidence = round(max(0.10, min(0.95, history_factor * volatility_factor)), 4)

    result: list[ForecastPoint] = []
    for step in range(1, horizon_days + 1):
        predicted = max(0.0, base + trend * step)
        margin = 1.96 * deviation * sqrt(1 + step / max(1, len(recent)))
        result.append(
            ForecastPoint(
                forecast_date=last_date + timedelta(days=step),
                predicted_qty=round(predicted, 4),
                lower_qty=round(max(0.0, predicted - margin), 4),
                upper_qty=round(predicted + margin, 4),
                confidence=confidence,
            )
        )
    return result


def restock_quantity(
    forecast_qty: float,
    current_stock: int,
    reserved_stock: int,
    safety_stock: int,
) -> int:
    available = max(0, current_stock - reserved_stock)
    demand_with_safety = max(0.0, forecast_qty) + max(0, safety_stock)
    return max(0, int(demand_with_safety + 0.9999) - available)


def stockout_risk(reorder_qty: int, forecast_qty: float, available_stock: int) -> str:
    if reorder_qty <= 0:
        return "low"
    if available_stock <= 0 or reorder_qty >= max(1.0, forecast_qty):
        return "critical"
    if available_stock < forecast_qty * 0.5:
        return "high"
    return "medium"

