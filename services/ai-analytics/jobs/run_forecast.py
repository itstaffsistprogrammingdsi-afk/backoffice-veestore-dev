from app.config import get_settings
from app.database import get_analytics_engine, get_source_engine
from app.pipeline import run_forecast_pipeline


if __name__ == "__main__":
    print(run_forecast_pipeline(get_source_engine(), get_analytics_engine(), get_settings()))
