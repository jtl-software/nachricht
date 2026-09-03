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
    # Maintained fork, tracks current RabbitMQ releases (Khepri/Leveled storage). Ships a .zip
    # whose name carries the Erlang version it was built against - and that moves with the
    # broker (4.3.1 shipped erlang-26, 4.3.3 ships erlang-27). Newest first, take what exists,
    # so a plugin release built against a newer Erlang does not need an edit here.
    downloaded=""
    for erlang in 29 28 27 26; do
      if curl -fsSL -o /tmp/plugin.zip \
        "https://github.com/cloudamqp/rabbitmq-delayed-message-exchange/releases/download/v${version}/rabbitmq_delayed_message_exchange-${version}-erlang-${erlang}.zip"; then
        echo "using plugin ${version} built for erlang ${erlang}"
        downloaded=1
        break
      fi
    done
    if [ -z "$downloaded" ]; then
      echo "no cloudamqp plugin asset found for version ${version}" >&2
      exit 1
    fi
    unzip -o /tmp/plugin.zip -d "${plugins_dir}"
    rm /tmp/plugin.zip
    ;;
  *)
    echo "unknown PLUGIN_SOURCE: ${source}" >&2
    exit 1
    ;;
esac
