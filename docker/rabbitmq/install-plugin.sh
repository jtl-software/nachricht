#!/bin/sh
# Installs the rabbitmq_delayed_message_exchange plugin from one of two sources,
# selected by $1 (PLUGIN_SOURCE), at version $2 (PLUGIN_VERSION).
set -e

source="$1"
version="$2"
plugins_dir="${RABBITMQ_HOME:-/opt/rabbitmq}/plugins"

case "$source" in
  archived)
    # Pre-upgrade plugin, archived by the RabbitMQ team. Ships a single .ez file per release.
    curl -fsSL -o "${plugins_dir}/rabbitmq_delayed_message_exchange.ez" \
      "https://github.com/rabbitmq/rabbitmq-delayed-message-exchange/releases/download/v${version}/rabbitmq_delayed_message_exchange-${version}.ez"
    ;;
  cloudamqp)
    # Maintained fork, tracks current RabbitMQ releases (Khepri/Leveled storage). Ships a .zip.
    curl -fsSL -o /tmp/plugin.zip \
      "https://github.com/cloudamqp/rabbitmq-delayed-message-exchange/releases/download/v${version}/rabbitmq_delayed_message_exchange-${version}-erlang-26.zip"
    unzip -o /tmp/plugin.zip -d "${plugins_dir}"
    rm /tmp/plugin.zip
    ;;
  *)
    echo "unknown PLUGIN_SOURCE: ${source}" >&2
    exit 1
    ;;
esac
