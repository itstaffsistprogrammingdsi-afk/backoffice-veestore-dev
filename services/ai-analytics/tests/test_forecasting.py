import unittest
from datetime import date, timedelta

from app.forecasting import moving_average_forecast, restock_quantity, stockout_risk


class ForecastingTest(unittest.TestCase):
    def test_constant_demand_forecast(self):
        start = date(2026, 1, 1)
        observations = {start + timedelta(days=i): 5 for i in range(28)}
        result = moving_average_forecast(observations, horizon_days=7)
        self.assertEqual(7, len(result))
        self.assertTrue(all(point.predicted_qty == 5 for point in result))

    def test_missing_days_are_zero_filled(self):
        observations = {date(2026, 1, 1): 7, date(2026, 1, 7): 7}
        result = moving_average_forecast(observations, horizon_days=1)
        self.assertEqual(2, result[0].predicted_qty)

    def test_restock_never_negative(self):
        self.assertEqual(0, restock_quantity(7, 20, 0, 3))
        self.assertEqual(8, restock_quantity(10, 5, 1, 2))
        self.assertEqual("critical", stockout_risk(10, 10, 0))


if __name__ == "__main__":
    unittest.main()

