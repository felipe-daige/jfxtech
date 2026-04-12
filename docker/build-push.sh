#!/bin/bash
set -e

VERSION=$1

if [ -z "$VERSION" ]; then
  APP_TAG="app"
  WEB_TAG="webserver"
else
  APP_TAG="app-$VERSION"
  WEB_TAG="webserver-$VERSION"
fi

echo "Building felipedaige/jfxtech:$APP_TAG ..."
docker build -t felipedaige/jfxtech:$APP_TAG .

echo "Building felipedaige/jfxtech:$WEB_TAG ..."
docker build -f Dockerfile.webserver -t felipedaige/jfxtech:$WEB_TAG .

echo "Logging in to Docker Hub..."
docker login

echo "Pushing felipedaige/jfxtech:$APP_TAG ..."
docker push felipedaige/jfxtech:$APP_TAG

echo "Pushing felipedaige/jfxtech:$WEB_TAG ..."
docker push felipedaige/jfxtech:$WEB_TAG

echo "Done! Images published:"
echo "  docker pull felipedaige/jfxtech:$APP_TAG"
echo "  docker pull felipedaige/jfxtech:$WEB_TAG"
