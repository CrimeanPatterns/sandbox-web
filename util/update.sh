#!/usr/bin/env bash
set -eux pipefail

SOURCE_PATH=../awardwallet

cp $SOURCE_PATH/src/kernel/browserExt.js src/kernel/
cp $SOURCE_PATH/src/js/routes.js src/js/
cp $SOURCE_PATH/src/bundles/fosjsrouting/js/router.js src/bundles/fosjsrouting/js/
cp $SOURCE_PATH/src/extension/ExtensionCommunicator.js src/extension/
cp $SOURCE_PATH/src/extension/CallbackManager.js src/extension/
cp $SOURCE_PATH/src/extension/main.js src/extension/

echo done