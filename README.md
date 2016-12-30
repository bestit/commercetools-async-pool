# commercetools-async-pool

Batch-Processing of a pool asynchronous commercetools requests.

## Introdction

Commercetools suggests that you use [asynchronous requests instead of sequential ones](https://dev.commercetools.com/best-practices-performance.html#api-request-planning) but the [PHP SDK](https://github.com/commercetools/commercetools-php-sdk) makes it not very easy:

1. Promises for Client::executeAsync works on the raw guzzle response, not the "requested object".
2. [Guzzle promise-chaining/forwarding](https://github.com/guzzle/promises#promise-forwarding) and the AbstractApiResponse from commercetools are not compatible.

So i created a helping pool of async requests. Please review the following information.

## Installation
    composer require bestit/commercetools-async-pool
    
## API and Usage

```php
$pool = new Pool($this->getClient(), 25);

$pool->addPromise(
    ProductTypeByIdGetRequest::ofId('example'),
    // Success
    function(ProductType $productType) use ($userdata) {          
        echo $productType->getId();
    },
    // optional error callback
    function(ErrorResponse $error) {
        echo $error->getStatusCode();    
    }
);

// Gets flushed automatically, if we overflow the tick rate from the constructor.
$pool->flush();

```

**But beware, do not forget that the callbacks are happening asynchronous! That is no sequential PHP anymore!**

## Future

# Get a more native promise API!
# Unittests
