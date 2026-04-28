CREATE TABLE IF NOT EXISTS remember_tokens (
  id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  selector VARCHAR(64) NOT NULL,
  token_hash TEXT NOT NULL,
  user_agent TEXT,
  expires_at TIMESTAMPTZ NOT NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  CONSTRAINT remember_tokens_selector_key UNIQUE (selector)
);

CREATE INDEX IF NOT EXISTS remember_tokens_user_id_idx ON remember_tokens (user_id);
CREATE INDEX IF NOT EXISTS remember_tokens_expires_at_idx ON remember_tokens (expires_at);
