function generateCacheKey($object)
{
    return md5(json_encode($object));
}
function isCacheExists($cacheKey)
{
    return file_exists('cache/' . $cacheKey);
}
function getFromCache($cacheKey)
{
    return json_decode(file_get_contents('cache/' . $cacheKey), true);
}
function storeInCache($cacheKey, $data)
{
    file_put_contents('cache/' . $cacheKey, json_encode($data));
}
function emptyResponse(){
    // Logic for sending an empty response
}
$cacheKey = generateCacheKey($body)
if (isCacheExists($cacheKey)) {
    $cachedData = getFromCache($cacheKey);
    // Logic for returning cached data
} else {
    // Logic for processing the request and generating data
    storeInCache($cacheKey, $data);
    // Logic for returning generated data
}
