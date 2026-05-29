#!/bin/bash

set -xe

FORCE_FLAG=""

while getopts "f" opt; do
  case $opt in
    f)
      FORCE_FLAG="-f"
      ;;
    \?)
      echo "Invalid option: -$OPTARG" >&2
      exit 1
      ;;
  esac
done
shift $((OPTIND-1))


# The first argument is now the path to composer.json
COMPOSER_JSON_PATH=$1
# The second argument is the git ref
GIT_REF=$2

if [ -z "$COMPOSER_JSON_PATH" ] || [ -z "$GIT_REF" ]; then
    echo "Usage: $0 [-f] <path-to-composer.json> <git-ref>"
    exit 1
fi

package=$(jq -r .name "$COMPOSER_JSON_PATH")
directory=$(dirname "$COMPOSER_JSON_PATH")
repository="https://github.com/$package"

# Add the remote only if it doesn't exist already
if ! git remote get-url "$package" > /dev/null 2>&1; then
    git remote add "$package" "$repository"
fi

sha=$(splitsh-lite --prefix="$directory")
git push $FORCE_FLAG "$package" "$sha":"$GIT_REF"


