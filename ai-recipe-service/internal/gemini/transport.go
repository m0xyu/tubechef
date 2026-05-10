package gemini

import (
	"bytes"
	"errors"
	"fmt"
	"io"
	"log/slog"
	"net/http"
	"time"

	"context"

	"github.com/m0xyu/tubechef/ai-recipe-service/domain"
)

const (
	maxRetries     = 3
	retryBaseDelay = 2 * time.Second
)

func (c *Client) post(ctx context.Context, body []byte) ([]byte, error) {
	url := fmt.Sprintf("%s/v1beta/models/%s:generateContent?key=%s", c.baseURL, c.model, c.apiKey)

	var lastErr error
	for attempt := range maxRetries {
		if attempt > 0 {
			delay := retryBaseDelay * (1 << (attempt - 1)) // 2s, 4s
			slog.Info("Gemini retry", "attempt", attempt+1, "delay", delay)
			select {
			case <-ctx.Done():
				return nil, ctx.Err()
			case <-time.After(delay):
			}
		}

		raw, err := c.doPost(ctx, url, body)
		if err == nil {
			return raw, nil
		}

		if isRetryable(err) {
			lastErr = err
			continue
		}
		return nil, err
	}
	return nil, lastErr
}

func (c *Client) doPost(ctx context.Context, url string, body []byte) ([]byte, error) {
	req, err := http.NewRequestWithContext(ctx, http.MethodPost, url, bytes.NewReader(body))
	if err != nil {
		return nil, fmt.Errorf("create request: %w", err)
	}
	req.Header.Set("Content-Type", "application/json")

	resp, err := c.httpClient.Do(req)
	if err != nil {
		return nil, fmt.Errorf("%w: %w", domain.ErrUnavailable, err)
	}
	defer resp.Body.Close()

	raw, err := io.ReadAll(resp.Body)
	if err != nil {
		return nil, fmt.Errorf("read response body: %w", err)
	}

	if resp.StatusCode != http.StatusOK {
		err := mapHTTPError(resp.StatusCode)
		slog.Error("Gemini API error", "status", resp.StatusCode, "body", string(raw), "error", err)
		return nil, err
	}

	return raw, nil
}

func isRetryable(err error) bool {
	return errors.Is(err, domain.ErrResourceExhausted) || errors.Is(err, domain.ErrUnavailable)
}

// mapHTTPError は HTTP ステータスコードをドメインエラーに変換する純粋関数
func mapHTTPError(status int) error {
	switch status {
	case http.StatusBadRequest:
		return domain.ErrInvalidArgument
	case http.StatusUnauthorized:
		return domain.ErrPermissionDenied
	case http.StatusForbidden:
		return domain.ErrPermissionDenied
	case http.StatusNotFound:
		return domain.ErrNotFound
	case http.StatusTooManyRequests:
		return domain.ErrResourceExhausted
	case http.StatusServiceUnavailable:
		return domain.ErrUnavailable
	case http.StatusGatewayTimeout:
		return domain.ErrDeadlineExceeded
	default:
		return domain.ErrInternalServerError
	}
}
