FROM php:8.2

ARG AWS_ACCESS_KEY_ID
ARG AWS_SECRET_ACCESS_KEY

USER root

RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    gnupg

RUN curl -fsSL https://deb.nodesource.com/setup_lts.x | bash -\
    && apt-get install -y nodejs \
    && npm install -g npm \
    && npm install -g serverless@3

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN mkdir -p /root/.aws && \
    echo "[default]" > /root/.aws/credentials && \
    echo "aws_access_key_id=$AWS_ACCESS_KEY_ID" >> /root/.aws/credentials && \
    echo "aws_secret_access_key=$AWS_SECRET_ACCESS_KEY" >> /root/.aws/credentials

WORKDIR /var/app
