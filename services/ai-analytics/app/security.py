import secrets

from fastapi import Header, HTTPException, status

from app.config import get_settings


def require_api_token(x_api_token: str = Header(default="")) -> None:
    expected = get_settings().api_token
    if not x_api_token or not secrets.compare_digest(x_api_token, expected):
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="API token tidak valid.",
        )

