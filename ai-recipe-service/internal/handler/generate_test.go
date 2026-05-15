package handler_test

import (
	"bytes"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/m0xyu/tubechef/ai-recipe-service/domain"
	"github.com/m0xyu/tubechef/ai-recipe-service/internal/handler"
	"github.com/m0xyu/tubechef/ai-recipe-service/internal/testutil"
	"github.com/m0xyu/tubechef/ai-recipe-service/internal/utils"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
)

func newRequest(t *testing.T, body any) *http.Request {
	t.Helper()
	b, err := json.Marshal(body)
	require.NoError(t, err)
	r := httptest.NewRequest(http.MethodPost, "/generate", bytes.NewReader(b))
	r.Header.Set("Content-Type", "application/json")
	return r
}

func TestGenerateHandler_Success(t *testing.T) {
	mock := &testutil.MockRecipeGenerator{
		Recipe:   testutil.SampleGeneratedRecipe(),
		Metadata: testutil.SampleLLMMetadata(),
	}
	h := handler.NewGenerateHandler(mock)

	w := httptest.NewRecorder()
	h.ServeHTTP(w, newRequest(t, map[string]any{
		"video_id":    "abc123",
		"title":       "鶏の唐揚げ",
		"description": "材料...",
	}))

	assert.Equal(t, http.StatusOK, w.Code)

	var resp utils.Response
	require.NoError(t, json.Unmarshal(w.Body.Bytes(), &resp))
	assert.True(t, resp.Success)
}

func TestGenerateHandler_MissingVideoID(t *testing.T) {
	mock := &testutil.MockRecipeGenerator{
		Recipe:   testutil.SampleGeneratedRecipe(),
		Metadata: testutil.SampleLLMMetadata(),
	}
	h := handler.NewGenerateHandler(mock)

	w := httptest.NewRecorder()
	h.ServeHTTP(w, newRequest(t, map[string]any{
		"title": "鶏の唐揚げ",
	}))

	assert.Equal(t, http.StatusBadRequest, w.Code)

	var resp utils.Response
	require.NoError(t, json.Unmarshal(w.Body.Bytes(), &resp))
	assert.False(t, resp.Success)
}

func TestGenerateHandler_MissingTitle(t *testing.T) {
	mock := &testutil.MockRecipeGenerator{
		Recipe:   testutil.SampleGeneratedRecipe(),
		Metadata: testutil.SampleLLMMetadata(),
	}
	h := handler.NewGenerateHandler(mock)

	w := httptest.NewRecorder()
	h.ServeHTTP(w, newRequest(t, map[string]any{
		"video_id": "abc123",
	}))

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestGenerateHandler_NotRecipe(t *testing.T) {
	mock := &testutil.MockRecipeGenerator{Err: domain.ErrNotRecipeError}
	h := handler.NewGenerateHandler(mock)

	w := httptest.NewRecorder()
	h.ServeHTTP(w, newRequest(t, map[string]any{
		"video_id": "abc123",
		"title":    "ゲーム実況",
	}))

	assert.Equal(t, http.StatusUnprocessableEntity, w.Code)

	var resp utils.Response
	require.NoError(t, json.Unmarshal(w.Body.Bytes(), &resp))
	assert.False(t, resp.Success)
}

func TestGenerateHandler_RateLimit(t *testing.T) {
	mock := &testutil.MockRecipeGenerator{Err: domain.ErrResourceExhausted}
	h := handler.NewGenerateHandler(mock)

	w := httptest.NewRecorder()
	h.ServeHTTP(w, newRequest(t, map[string]any{
		"video_id": "abc123",
		"title":    "料理動画",
	}))

	assert.Equal(t, http.StatusTooManyRequests, w.Code)
}

func TestGenerateHandler_ErrorCode(t *testing.T) {
	tests := []struct {
		name          string
		err           error
		wantStatus    int
		wantErrorCode string
	}{
		{"not_a_recipe", domain.ErrNotRecipeError, http.StatusUnprocessableEntity, "not_a_recipe"},
		{"resource_exhausted", domain.ErrResourceExhausted, http.StatusTooManyRequests, "resource_exhausted"},
		{"permission_denied", domain.ErrPermissionDenied, http.StatusForbidden, "permission_denied"},
		{"unavailable", domain.ErrUnavailable, http.StatusServiceUnavailable, "unavailable"},
		{"generation_failed", domain.ErrGenerationFailed, http.StatusInternalServerError, "generation_failed"},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			mock := &testutil.MockRecipeGenerator{Err: tt.err}
			h := handler.NewGenerateHandler(mock)

			w := httptest.NewRecorder()
			h.ServeHTTP(w, newRequest(t, map[string]any{
				"video_id": "abc123",
				"title":    "テスト動画",
			}))

			assert.Equal(t, tt.wantStatus, w.Code)

			var resp utils.Response
			require.NoError(t, json.Unmarshal(w.Body.Bytes(), &resp))
			assert.False(t, resp.Success)
			assert.Equal(t, tt.wantErrorCode, resp.ErrorCode)
		})
	}
}

func TestGenerateHandler_InvalidJSON(t *testing.T) {
	mock := &testutil.MockRecipeGenerator{}
	h := handler.NewGenerateHandler(mock)

	r := httptest.NewRequest(http.MethodPost, "/generate", bytes.NewBufferString("invalid json"))
	r.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()
	h.ServeHTTP(w, r)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}
