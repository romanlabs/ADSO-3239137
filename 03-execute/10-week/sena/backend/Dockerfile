FROM node:22-alpine AS build
WORKDIR /app
COPY package.json ./
RUN npm install
COPY tsconfig.json ./
COPY src/ ./src/
RUN npm run build

FROM node:22-alpine
RUN apk add --no-cache wget
WORKDIR /app
COPY --from=build /app/package.json ./
RUN npm install --omit=dev
COPY --from=build /app/dist ./dist
ENV PORT=3001
ENV NODE_ENV=production
EXPOSE 3001
CMD ["node", "dist/server.js"]