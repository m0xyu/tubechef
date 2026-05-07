FROM golang:1.26-alpine AS builder

WORKDIR /app

COPY go.mod go.sum ./
RUN go mod download

COPY . .
RUN CGO_ENABLED=0 GOOS=linux go build -o /ai-recipe-service ./cmd/api/main.go

FROM alpine:3.20

RUN apk --no-cache add wget

WORKDIR /app
COPY --from=builder /ai-recipe-service .

EXPOSE 3000

CMD ["/app/ai-recipe-service"]
