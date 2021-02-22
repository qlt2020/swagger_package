# swagger_package

Данный пакет предназначен для генерациия сваггера с конфигурации open-api 

Принцип работы заключаеться в генерации конфигурации open-api. на Основе роутов апишки. Входящие параметры нужно указывать в реквесте. Остальное береться из аргументов метода контроллера


Пример контроллера 
```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthLoginRequest;
use Auth;

class AuthController extends Controller
{

    final public function login (AuthLoginRequest $request)
    {
        if (!Auth::attempt(['login' => $request->input('login'), 'password' => $request->input('password')]) )
            return $this->false('Wrong credentials');

        $user = Auth::user();

        return $user;
    }

}
```
