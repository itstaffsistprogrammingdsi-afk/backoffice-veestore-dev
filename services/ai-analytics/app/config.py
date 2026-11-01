from functools import lru_cache

from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    app_name: str = "Immostore AI Analytics"
    app_env: str = "local"
    app_host: str = "127.0.0.1"
    app_port: int = 8000
    source_database_url: str
    analytics_database_url: str
    api_token: str
    forecast_lookback_days: int = 90
    forecast_horizon_days: int = 7
    min_history_days: int = 14
    min_sales_days: int = 7
    business_timezone: str = "Asia/Jakarta"

    model_config = SettingsConfigDict(env_file=".env", extra="ignore")


@lru_cache
def get_settings() -> Settings:
    return Settings()
