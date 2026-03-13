FROM node:20-alpine as build-stage

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY . .

ARG VITE_API_BASE_URL=http://localhost:8000/api
ARG VITE_MERCURE_URL=http://localhost:3000/.well-known/mercure

ENV VITE_API_BASE_URL=$VITE_API_BASE_URL
ENV VITE_MERCURE_URL=$VITE_MERCURE_URL

RUN npm run build

FROM nginx:alpine as production-stage

COPY --from=build-stage /app/dist /usr/share/nginx/html
COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
