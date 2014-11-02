#!/bin/sh

if [ "$TRAVIS_PULL_REQUEST" == "true" ];
then
     exit 0;
fi

echo -e "Starting to update Joomla! 2.* zip files.\n"
BRANCH="joomla2"

export PACK_NAME="pkg_pro6pp"

/bin/bash zip_creator_Joomla.sh $REPO_BASE &&

#Go to home and setup git
cd $HOME
git config --global user.email "travis@travis-ci.org"
git config --global user.name "Travis"

#Using token, clone stable branch
git clone --quiet --branch="$BRANCH" https://${GH_TOKEN}@github.com/dcentralize/pro6pp-joomla.git  "$BRANCH" > /dev/null

#Go into directory and copy data we're interested in to that directory
# Hack the repository and empty all unecessary files if any.
mkdir -p tmp_branch;
cp -r ./"$BRANCH"/* ./tmp_branch/
rm -r ./"$BRANCH"/*
rm ./"$BRANCH"/.travis.yml
rm ./"$BRANCH"/.gitignore
cp -r ./tmp_branch/.git ./"$BRANCH"/
cd $BRANCH;

cp -Rf $REPO_BASE/"${PACK_NAME}.tar.gz" .

#Add, commit and push files
git add -Af .
git commit -m "Travis build $TRAVIS_BUILD_NUMBER pushed to $BRANCH branch."
git push -fq origin "$BRANCH" > /dev/null

echo -e "Finsihed deploying.\n"
