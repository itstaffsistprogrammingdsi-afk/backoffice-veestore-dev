from fastapi import Depends, FastAPI
from sqlalchemy import text

from app.config import get_settings
from app.database import get_analytics_engine, get_source_engine
from app.pipeline import run_forecast_pipeline
from app.security import require_api_token

settings = get_settings()
app = FastAPI(title=settings.app_name, version="1.0.0")


@app.get("/health")
def health() -> dict:
    databases = {"source": "ok", "analytics": "ok"}
    try:
        with get_source_engine().connect() as conn:
            conn.execute(text("SELECT 1"))
    except Exception:
        databases["source"] = "unavailable"
    try:
        with get_analytics_engine().connect() as conn:
            conn.execute(text("SELECT 1"))
    except Exception:
        databases["analytics"] = "unavailable"
    healthy = all(value == "ok" for value in databases.values())
    return {"status": "ok" if healthy else "degraded", "databases": databases, "version": "1.0.0"}


@app.post("/api/v1/jobs/forecast", dependencies=[Depends(require_api_token)])
def forecast() -> dict:
    run_id = run_forecast_pipeline(get_source_engine(), get_analytics_engine(), settings)
    return {"status": "completed", "model_run_id": str(run_id)}
