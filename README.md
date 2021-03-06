# laravel-zimbra

## Quick start

Install the package:

```bash
composer require diegosouza/laravel-zimbra --dev
```

Publish the configuration file using:

```bash
php artisan vendor:publish --provider="DiegoSouza\Zimbra\ZimbraServiceProvider"
```

Then provide the values to `config/zimbra.php`.


## Usage

The wrapper class is `DiegoSouza\Zimbra\ZimbraApiClient`. You can have it injected somewhere:

```php
namespace App\Http\Controllers;
  
use DiegoSouza\Zimbra\ZimbraApiClient;
  
class YourController extends Controller
{
    public function index(ZimbraApiClient $zimbra)
    {
        $result = $zimbra->getAllCos();

        // use the api result
    }
}
```

Or you can use it's methods through the Facade:

```php
namespace App\Http\Controllers;
  
use DiegoSouza\Zimbra\Facades\Zimbra;
  
class YourController extends Controller
{
    public function index()
    {
        $result = Zimbra::getAllCos();

        // use the api result
    }
}
```