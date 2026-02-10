FROM scratch
COPY classes /app/classes/
COPY 3dParty/plancake/ /app/3dParty/plancake/
COPY constants.php functions.php geoFunctions.php imageFunctions.php textFunctions.php /app/