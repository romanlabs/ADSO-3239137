# Stage 1: build the static bundle. VITE_API_URL is baked in at build time (it is the API origin seen by the browser).
# Debian slim, not Alpine: the native parser of Rollup fails on musl with "Code is nested too deeply".
FROM node:20-bookworm-slim AS build
WORKDIR /srv/app
COPY package.json ./
RUN npm install --no-audit --no-fund
COPY . .
ARG VITE_API_URL=http://localhost:8000
ENV VITE_API_URL=${VITE_API_URL}
RUN npm run build

# Stage 2: serve it with nginx on 5173, the origin the API allows in CORS_ORIGIN.
FROM nginx:1.27-alpine
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build /srv/app/dist /usr/share/nginx/html
EXPOSE 5173
