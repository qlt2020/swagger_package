# swagger_package

Данный пакет предназначен для генерации сваггера с конфигурации open-api 
Принцип работы заключается в генерации конфигурации open-api. на Основе роутов апишки. Входящие параметры нужно указывать в реквесте. Остальное будет взято из аргументов метода контроллера.

## Установка приватного пакета
1. Указываем в файле композера приватный репозитарий 
```
"repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:qlt2020/swagger_package.git"
        }
 ]
``` 
2. Привязываем пакет к ларке 
``` 
"hmurich/swagger": "^1.0"
```
3. Запускаем composer update. В процессе появиться ссылка в командной строке на генерацию токена доступа(гитхаба). Его вбиваем в командную строку. 
4. Все можем юзать

## Алгоритм работы пакета
1.Добавляем реквест. По указанным правилам валидации, будут формироваться поля запроса в свагере. Пример: 
```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuthLoginRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'login' => 'required|string',
            'password' => 'required|string',
            'sample' => 'string'
        ];
    }
}
```
2. Указываем в контроллере ранее описанный реквест. Пример: 
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
3. Привязываем контроллер к роуту. Пример: 
```php
    Route::post('login', [AuthController::class, 'login']);
```
4. запускаем команду в консоле для генерации конфигурации 
```
php artisan swagger:generate
```
5. указываем роут для вывода swagger-ui (разово)
```php
    
use Hmurich\Swagger\Controllers\SwaggerViewController;

Route::get('swagger/ui', [SwaggerViewController::class, 'index']);
```
6. указываем именования полей, через трайнслайтор.
![image](https://user-images.githubusercontent.com/12165549/108693870-8f678580-7528-11eb-8343-8fae8303d82a.png)
После этого заново запускаем комманду на генерацию конфигурации и получаем конечный результат. 


Также есть возможности конфигурации. Для этого опубликуйте конфиги этого пакета. После этого будет доступен файл конфигурации 
```php 

return [
    'url_to_openapi' => '/data/api.json',
    'title' => 'Swagger UI',
    'version' => '1',
    'description' => 'description',
    'api_prefix' => 'api',
    'json_file' => 'data/api.json',
    'has_auth' => true
];

```

