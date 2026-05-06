# ai-recipe-service/Makefile

.PHONY: build run dev lint format tidy

run:
	go run ./cmd/api/main.go

test: 
	go test -v ./...

dev:
	air # （後でAirを使ったホットリロードを導入する場合）

tidy:
	go mod tidy

lint: format
	golangci-lint run ./...

format:
	@gofmt -s -w .
	@goimports -w .