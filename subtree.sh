#!/bin/bash

set -e # Exit immediately if a command exits with a non-zero status.
set -x

GITHUB_REF="$1"

if [[ -z "${GITHUB_REF}" ]]; then
    echo "Usage: $0 GITHUB_REF"
    exit 129
fi

while IFS= read -r -d '' file
do

  package=$(jq -r .name "${file}")
  directory=$(dirname "${file}")
  repository="https://github.com/${package}"

  git remote add "${package}" "${repository}"
  sha=$(splitsh-lite --prefix="${directory}")
  git push "${package}" "${sha}:${GITHUB_REF}"

done < <(find src -maxdepth 3 -name composer.json -not -path '*/vendor/*' -print0)
