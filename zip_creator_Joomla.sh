#!/bin/bash

#This file needs a folder passed as argument to work
if [ -z "$1" ]; then
    echo 'Please specify the base folder that contains the component and plug-in folders.'
    exit 1
fi

CUR_DIR=`pwd`

# Base folder containing the plug-in and component source folders
DIR="$1"
# The respective folders
PLUGIN="$DIR/plugin"
COMPONENT="$DIR/component"

if [ ! -d "$PLUGIN" ]; then
    echo 'Please specify the correct folder (repository path).\
    Containing the component and plugin folder.'
    exit 1;
elif [ ! -d "$COMPONENT" ]; then
    echo 'Please specify the correct folder (repository path).\
          Containing the component and plugin folder.'
    exit 1;
fi

# The desired names of the archives
#PACK_NAME='pkg_pro6pp'
PLG_NAME='plg_pro6pp'
COMP_NAME='com_pro6pp'

# Pack the component and plug-in seperatelly and then pack the archives
# together with the xml file
cd $DIR/plugin
tar -zcf $PLG_NAME.tar.gz * -C $DIR --exclude=Tests && \
mv $DIR/plugin/$PLG_NAME.tar.gz $DIR

cd $DIR/component
tar -zcf $COMP_NAME.tar.gz  * -C $DIR --exclude=Tests && \
mv $DIR/component/$COMP_NAME.tar.gz $DIR

if [ ! -e "$DIR/pkg_pro6pp.xml" ];then
    echo 'In the root folder needs to exist the\
    installation (pkg_pro6pp.xml) file.'
    exit 1
else
    cd $DIR
    echo 'Creating package tar.'
    tar -zcf $PACK_NAME.tar.gz pkg_pro6pp.xml \
             $PLG_NAME.tar.gz $COMP_NAME.tar.gz -C $DIR
fi

# Remove the stand-alone archives
if [ -e "$DIR/$PLG_NAME.tar.gz" ]; then
    echo 'Removing plugin tar.'
    rm $DIR/$PLG_NAME.tar.gz
fi
if [ -e "$DIR/$COMP_NAME.tar.gz" ]; then
    echo 'Removing component tar.'
    rm $DIR/$COMP_NAME.tar.gz
fi
echo 'Operation completed.'

cd $CUR_DIR