package config

import (
	"fmt"
	"os"

	"github.com/joho/godotenv"
)

type Config struct {
	Port          string
	GeminiAPIKey  string
	GeminiBaseURL string
	GeminiModel   string
}

// Load は .env を読み込み（存在する場合のみ）、環境変数を Config に詰めて返す
func Load() (*Config, error) {
	// 本番では .env がないのが正常なため、エラーは無視する
	_ = godotenv.Load()

	cfg := &Config{
		Port:          getEnv("PORT", "3000"),
		GeminiAPIKey:  os.Getenv("GEMINI_API_KEY"),
		GeminiBaseURL: getEnv("GEMINI_BASE_URL", "https://generativelanguage.googleapis.com"),
		GeminiModel:   getEnv("GEMINI_MODEL", "gemini-2.5-flash"),
	}

	if err := cfg.validate(); err != nil {
		return nil, err
	}

	return cfg, nil
}

func (c *Config) validate() error {
	if c.GeminiAPIKey == "" {
		return fmt.Errorf("GEMINI_API_KEY is required")
	}
	return nil
}

func getEnv(key, defaultValue string) string {
	if v := os.Getenv(key); v != "" {
		return v
	}
	return defaultValue
}
