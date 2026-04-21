# ai-recipe-service/Makefile

.PHONY: build run dev lint format

run:
	go run ./cmd/api/main.go

dev:
	air # （後でAirを使ったホットリロードを導入する場合）

lint: format
	golangci-lint run ./...

format:
	@gofmt -s -w .
	@goimports -w .