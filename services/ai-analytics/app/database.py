from functools import lru_cache

from sqlalchemy import create_engine
from sqlalchemy.engine import Engine

from app.config import get_settings


@lru_cache
def get_source_engine() -> Engine:
    return create_engine(get_settings().source_database_url, pool_pre_ping=True, future=True)


@lru_cache
def get_analytics_engine() -> Engine:
    return create_engine(get_settings().analytics_database_url, pool_pre_ping=True, future=True)
