package utils

import (
	"errors"
	"net/http"

	"github.com/go-chi/render"
	"github.com/m0xyu/tubechef/ai-recipe-service/domain"
)

type Response struct {
	Success   bool   `json:"success"`
	Message   string `json:"message"`
	Data      any    `json:"data,omitempty"`
	Error     string `json:"error,omitempty"`
	ErrorCode string `json:"error_code,omitempty"`
}

func SuccessResponse(w http.ResponseWriter, r *http.Request, message string, data any) {
	render.Status(r, http.StatusOK)
	render.JSON(w, r, Response{
		Success: true,
		Message: message,
		Data:    data,
	})
}

func CreatedResponse(w http.ResponseWriter, r *http.Request, message string, data any) {
	render.Status(r, http.StatusCreated)
	render.JSON(w, r, Response{
		Success: true,
		Message: message,
		Data:    data,
	})
}

func errorCode(err error) string {
	switch {
	case errors.Is(err, domain.ErrNotRecipeError):
		return "not_a_recipe"
	case errors.Is(err, domain.ErrGenerationFailed):
		return "generation_failed"
	case errors.Is(err, domain.ErrResourceExhausted):
		return "resource_exhausted"
	case errors.Is(err, domain.ErrInvalidArgument):
		return "invalid_argument"
	case errors.Is(err, domain.ErrFailedPrecondition):
		return "failed_precondition"
	case errors.Is(err, domain.ErrPermissionDenied):
		return "permission_denied"
	case errors.Is(err, domain.ErrNotFound):
		return "not_found"
	case errors.Is(err, domain.ErrUnavailable):
		return "unavailable"
	case errors.Is(err, domain.ErrDeadlineExceeded):
		return "deadline_exceeded"
	default:
		return "internal_error"
	}
}

func ErrorResponse(w http.ResponseWriter, r *http.Request, err error) {
	var statusCode int
	var message string

	// 1. エラーの種類を判定
	switch {
	case errors.Is(err, domain.ErrNotRecipeError):
		statusCode = http.StatusUnprocessableEntity // 422
		message = err.Error()
	case errors.Is(err, domain.ErrResourceExhausted):
		statusCode = http.StatusTooManyRequests // 429
		message = err.Error()
	case errors.Is(err, domain.ErrInvalidArgument):
		statusCode = http.StatusBadRequest // 400
		message = err.Error()
	case errors.Is(err, domain.ErrFailedPrecondition):
		statusCode = http.StatusBadRequest // 400
		message = err.Error()
	case errors.Is(err, domain.ErrPermissionDenied):
		statusCode = http.StatusForbidden // 403
		message = err.Error()
	case errors.Is(err, domain.ErrNotFound):
		statusCode = http.StatusNotFound // 404
		message = err.Error()
	case errors.Is(err, domain.ErrUnavailable):
		statusCode = http.StatusServiceUnavailable // 503
		message = err.Error()
	case errors.Is(err, domain.ErrDeadlineExceeded):
		statusCode = http.StatusGatewayTimeout // 504
		message = err.Error()

	default:
		statusCode = http.StatusInternalServerError // 500
		message = "予期せぬエラーが発生しました。"
	}

	// 2. JSONを送信
	render.Status(r, statusCode)
	render.JSON(w, r, Response{
		Success:   false,
		Message:   message,
		Error:     err.Error(),
		ErrorCode: errorCode(err),
	})
}

func BadRequestResponse(w http.ResponseWriter, r *http.Request, message string) {
	render.Status(r, http.StatusBadRequest)
	render.JSON(w, r, Response{Success: false, Message: message})
}

func UnauthorizedResponse(w http.ResponseWriter, r *http.Request, message string) {
	render.Status(r, http.StatusUnauthorized)
	render.JSON(w, r, Response{Success: false, Message: message})
}

func ForbiddenResponse(w http.ResponseWriter, r *http.Request, message string) {
	render.Status(r, http.StatusForbidden)
	render.JSON(w, r, Response{Success: false, Message: message})
}

func NotFoundResponse(w http.ResponseWriter, r *http.Request, message string) {
	render.Status(r, http.StatusNotFound)
	render.JSON(w, r, Response{Success: false, Message: message})
}

func InternalServerErrorResponse(w http.ResponseWriter, r *http.Request, message string) {
	render.Status(r, http.StatusInternalServerError)
	render.JSON(w, r, Response{Success: false, Message: message})
}
