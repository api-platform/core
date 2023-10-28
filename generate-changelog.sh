#!/bin/bash
# usage: generate-changelog.sh previous_tag next_tag
# example: generate-changelog.sh v2.7.2 v2.7.3 > CHANGELOG.new.md
log=$(git log "$1..HEAD" --pretty='format:* [%h](https://github.com/api-platform/core/commit/%H) %s' --no-merges)

diff=$(
printf "# Changelog\n\n"
printf "## %s\n\n" "$2"

if [[ 0 != $(echo "$log" | grep fix | grep -v chore | wc -l) ]];
then
    printf "### Bug fixes\n\n"
    printf "$log" | grep fix | grep -v chore | sort
    printf "\n\n"
fi

if [[ 0 != $(echo "$log" | grep feat | grep -v chore | wc -l) ]];
then
    printf "### Features\n\n"
    printf "$log" | grep feat | grep -v chore | sort
fi
)

changelog=$(tail -n+2 CHANGELOG.md)
printf "%s\n%s" "$diff" "$changelog"
