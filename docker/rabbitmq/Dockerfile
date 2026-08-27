ARG RABBITMQ_VERSION=4.3.3-management
FROM rabbitmq:${RABBITMQ_VERSION}

ARG PLUGIN_SOURCE=cloudamqp
ARG PLUGIN_VERSION=4.3.1

# PLUGIN_SOURCE=archived  -> github.com/rabbitmq/rabbitmq-delayed-message-exchange (pre-upgrade plugin, .ez asset)
# PLUGIN_SOURCE=cloudamqp -> github.com/cloudamqp/rabbitmq-delayed-message-exchange (maintained fork, .zip asset)
COPY install-plugin.sh /tmp/install-plugin.sh

# The base image runs as the non-root "rabbitmq" user by default; the Erlang runtime refuses
# to trust /var/lib/rabbitmq/.erlang.cookie unless it was created by that same user (it rejects
# it with "eacces" otherwise, regardless of actual filesystem permission bits) - so build as root
# only for the package/plugin install, then switch back before the image's default user is set.
USER root
RUN apt-get update \
    && apt-get install -y --no-install-recommends curl unzip ca-certificates \
    && rm -rf /var/lib/apt/lists/* \
    && chmod +x /tmp/install-plugin.sh \
    && /tmp/install-plugin.sh "${PLUGIN_SOURCE}" "${PLUGIN_VERSION}" \
    && rabbitmq-plugins enable --offline rabbitmq_delayed_message_exchange \
    && rm /tmp/install-plugin.sh
USER rabbitmq
