FROM nginx:latest

RUN groupadd -r docker && useradd --no-log-init -r -g docker docker \
    && usermod -u 1000 docker \
    && groupmod -g 1000 docker \
    && rm /etc/nginx/conf.d/default.conf


# CMD ["nginx"]
WORKDIR /site

EXPOSE 80