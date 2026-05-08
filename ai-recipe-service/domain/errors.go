package domain

import (
	"errors"
)

var (
	// ErrInternalServerError will throw if any the Internal Server Error happen
	ErrInternalServerError = errors.New("internal Server Error")

	ErrNotRecipeError   = errors.New("料理カテゴリ外のため、生成対象外です。")
	ErrGenerationFailed = errors.New("AIによるレシピ生成に失敗しました。")

	ErrInvalidArgument    = errors.New("リクエストの形式が正しくありません。")
	ErrFailedPrecondition = errors.New("課金設定またはリージョンの制限により利用できません。")
	ErrPermissionDenied   = errors.New("APIキーの権限が不足しています。")
	ErrNotFound           = errors.New("指定されたリソースが見つかりません。")
	ErrResourceExhausted  = errors.New("レート制限を超過しました。しばらく待ってから再試行してください。")
	ErrUnavailable        = errors.New("サービスが利用できません。しばらく待ってから再試行してください。")
	ErrDeadlineExceeded   = errors.New("リクエストの処理に時間がかかりすぎました。しばらく待ってから再試行してください。")
)
