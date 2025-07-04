# Framework Integration Guide

This guide shows how to integrate Ray.InputQuery with popular PHP frameworks.

## Laravel Integration

### Basic Setup

```php
// app/Http/Controllers/UserController.php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Ray\Di\Injector;
use Ray\InputQuery\InputQuery;
use Ray\InputQuery\Attribute\Input;
use Ray\InputQuery\Attribute\InputFile;
use Koriym\FileUpload\FileUpload;
use Koriym\FileUpload\ErrorFileUpload;

final class UserController extends Controller
{
    public function store(Request $request)
    {
        $injector = new Injector();
        $inputQuery = new InputQuery($injector);
        
        $method = new \ReflectionMethod($this, 'createUser');
        $args = $inputQuery->getArguments($method, $request->all());
        
        return $this->createUser(...$args);
    }
    
    public function createUser(
        #[Input] string $name,
        #[Input] string $email,
        #[InputFile(
            maxSize: 5 * 1024 * 1024,
            allowedTypes: ['image/jpeg', 'image/png']
        )] FileUpload|ErrorFileUpload $avatar,
    ): array {
        if ($avatar instanceof ErrorFileUpload) {
            throw new \InvalidArgumentException($avatar->message);
        }
        
        $avatarPath = $avatar->move(storage_path('app/public/avatars'));
        
        // Create user logic here
        return ['success' => true, 'avatar' => $avatarPath];
    }
}
```

### Laravel Service Provider

```php
// app/Providers/InputQueryServiceProvider.php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Ray\Di\Injector;
use Ray\InputQuery\InputQuery;
use Ray\InputQuery\InputQueryInterface;

class InputQueryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(InputQueryInterface::class, function () {
            return new InputQuery(new Injector());
        });
    }
}
```

## Symfony Integration

### Basic Setup

```php
// src/Controller/UserController.php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Ray\Di\Injector;
use Ray\InputQuery\InputQuery;
use Ray\InputQuery\Attribute\Input;
use Ray\InputQuery\Attribute\InputFile;
use Koriym\FileUpload\FileUpload;
use Koriym\FileUpload\ErrorFileUpload;

class UserController extends AbstractController
{
    public function create(Request $request): JsonResponse
    {
        $injector = new Injector();
        $inputQuery = new InputQuery($injector);
        
        $method = new \ReflectionMethod($this, 'handleUserCreation');
        $args = $inputQuery->getArguments($method, $request->request->all());
        
        $result = $this->handleUserCreation(...$args);
        
        return new JsonResponse($result);
    }
    
    public function handleUserCreation(
        #[Input] string $name,
        #[Input] string $email,
        #[InputFile(maxSize: 5 * 1024 * 1024)] FileUpload|ErrorFileUpload|null $avatar = null,
    ): array {
        if ($avatar instanceof ErrorFileUpload) {
            throw new \InvalidArgumentException($avatar->message);
        }
        
        $avatarPath = null;
        if ($avatar instanceof FileUpload) {
            $avatarPath = $avatar->move($this->getParameter('upload_directory'));
        }
        
        // User creation logic
        return ['success' => true, 'avatar' => $avatarPath];
    }
}
```

### Symfony Service Configuration

```yaml
# config/services.yaml
services:
    Ray\InputQuery\InputQueryInterface:
        class: Ray\InputQuery\InputQuery
        arguments:
            - '@ray.di.injector'
    
    ray.di.injector:
        class: Ray\Di\Injector
```

## CakePHP Integration

### Basic Setup

```php
// src/Controller/UsersController.php
<?php

namespace App\Controller;

use Cake\Controller\Controller;
use Ray\Di\Injector;
use Ray\InputQuery\InputQuery;
use Ray\InputQuery\Attribute\Input;
use Ray\InputQuery\Attribute\InputFile;
use Koriym\FileUpload\FileUpload;
use Koriym\FileUpload\ErrorFileUpload;

class UsersController extends Controller
{
    public function add()
    {
        if ($this->request->is('post')) {
            $injector = new Injector();
            $inputQuery = new InputQuery($injector);
            
            $method = new \ReflectionMethod($this, 'processUserData');
            $args = $inputQuery->getArguments($method, $this->request->getData());
            
            $result = $this->processUserData(...$args);
            
            $this->set(['result' => $result]);
            $this->viewBuilder()->setOption('serialize', ['result']);
        }
    }
    
    public function processUserData(
        #[Input] string $name,
        #[Input] string $email,
        #[InputFile] FileUpload|ErrorFileUpload|null $profile_picture = null,
    ): array {
        if ($profile_picture instanceof ErrorFileUpload) {
            throw new \InvalidArgumentException($profile_picture->message);
        }
        
        $picturePath = null;
        if ($profile_picture instanceof FileUpload) {
            $picturePath = $profile_picture->move(WWW_ROOT . 'img' . DS . 'uploads');
        }
        
        // Process user data
        return ['success' => true, 'picture' => $picturePath];
    }
}
```

## BEAR.Sunday Integration

### Resource Class

```php
// src/Resource/App/User.php
<?php

namespace MyApp\Resource\App;

use BEAR\Resource\ResourceObject;
use Ray\InputQuery\Attribute\Input;
use Ray\InputQuery\Attribute\InputFile;
use Koriym\FileUpload\FileUpload;
use Koriym\FileUpload\ErrorFileUpload;

class User extends ResourceObject
{
    public function onPost(
        #[Input] string $name,
        #[Input] string $email,
        #[InputFile(
            maxSize: 5 * 1024 * 1024,
            allowedTypes: ['image/jpeg', 'image/png']
        )] FileUpload|ErrorFileUpload $avatar,
    ): static {
        if ($avatar instanceof ErrorFileUpload) {
            $this->code = 400;
            $this->body = ['error' => $avatar->message];
            return $this;
        }
        
        $avatarPath = $avatar->move('/path/to/uploads');
        
        // User creation logic
        $this->body = [
            'name' => $name,
            'email' => $email,
            'avatar' => $avatarPath
        ];
        
        return $this;
    }
}
```

## Yii Framework 1.x Integration

### Basic Setup

```php
// protected/controllers/UserController.php
<?php

use Ray\InputQuery\Attribute\Input;
use Ray\InputQuery\Attribute\InputFile;
use Ray\InputQuery\Attribute\InputQuery;
use Ray\Di\Injector;

class UserController extends CController
{
    public function actionCreate()
    {
        if (Yii::app()->request->isPostRequest) {
            $injector = new Injector();
            $inputQuery = new Query($injector);
            
            $method = new ReflectionMethod($this, 'handleUserCreation');
            $postData = array_merge($_POST, $_FILES);
            $args = $inputQuery->getArguments($method, $postData);
            
            $result = $this->handleUserCreation(...$args);
            
            $this->renderJSON($result);
        }
    }
    
    public function handleUserCreation(
        #[Input] string $name,
        #[Input] string $email,
        #[InputFile(
            maxSize: 5 * 1024 * 1024,
            allowedTypes: ['image/jpeg', 'image/png']
        )] Koriym\FileUpload\FileUpload|Koriym\FileUpload\ErrorFileUpload $avatar,
    ): array {
        if ($avatar instanceof Koriym\FileUpload\ErrorFileUpload) {
            throw new CHttpException(400, $avatar->message);
        }
        
        $uploadPath = Yii::getPathOfAlias('webroot.uploads');
        $avatarPath = $avatar->move($uploadPath);
        
        // User creation logic
        $user = new User();
        $user->name = $name;
        $user->email = $email;
        $user->avatar = $avatarPath;
        $user->save();
        
        return [
            'success' => true,
            'user' => $user->attributes,
            'avatar' => $avatarPath
        ];
    }
    
    private function renderJSON($data)
    {
        header('Content-Type: application/json');
        echo CJSON::encode($data);
        Yii::app()->end();
    }
}
```

### Component Configuration

```php
// protected/config/main.php
return array(
    'components' => array(
        'inputQuery' => array(
            'class' => 'application.extensions.inputquery.InputQueryComponent',
        ),
        // ... other components
    ),
);
```

### Custom Component Class

```php
// protected/extensions/inputquery/InputQueryComponent.php
<?php

class InputQueryComponent extends CComponent
{
    private $inputQuery;
    
    public function init()
    {
        parent::init();
        $injector = new Ray\Di\Injector();
        $this->inputQuery = new Ray\InputQuery\InputQuery($injector);
    }
    
    public function createInput($className, $data)
    {
        return $this->inputQuery->create($className, $data);
    }
    
    public function getMethodArguments($controller, $method, $data)
    {
        $reflection = new ReflectionMethod($controller, $method);
        return $this->inputQuery->getArguments($reflection, $data);
    }
}
```

### Form Helper Usage

```php
// protected/views/user/create.php
<?php echo CHtml::beginForm('', 'post', array('enctype' => 'multipart/form-data')); ?>

<div class="form-group">
    <?php echo CHtml::label('Name', 'name'); ?>
    <?php echo CHtml::textField('name', '', array('class' => 'form-control')); ?>
</div>

<div class="form-group">
    <?php echo CHtml::label('Email', 'email'); ?>
    <?php echo CHtml::emailField('email', '', array('class' => 'form-control')); ?>
</div>

<div class="form-group">
    <?php echo CHtml::label('Avatar', 'avatar'); ?>
    <?php echo CHtml::fileField('avatar', '', array('accept' => 'image/*')); ?>
    <small class="form-text text-muted">Max 5MB, JPEG/PNG only</small>
</div>

<?php echo CHtml::submitButton('Create User', array('class' => 'btn btn-primary')); ?>
<?php echo CHtml::endForm(); ?>
```

### Error Handling

```php
// protected/controllers/UserController.php
public function actionCreate()
{
    if (Yii::app()->request->isPostRequest) {
        try {
            $injector = new Ray\Di\Injector();
            $inputQuery = new Ray\InputQuery\InputQuery($injector);
            
            $method = new ReflectionMethod($this, 'handleUserCreation');
            $args = $inputQuery->getArguments($method, $_POST);
            
            $result = $this->handleUserCreation(...$args);
            
            $this->renderJSON($result);
        } catch (Exception $e) {
            $this->renderJSON([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
```

## Slim Framework Integration

### Basic Setup

```php
// src/Controller/UserController.php
<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Ray\Di\Injector;
use Ray\InputQuery\InputQuery;
use Ray\InputQuery\Attribute\Input;
use Ray\InputQuery\Attribute\InputFile;
use Koriym\FileUpload\FileUpload;
use Koriym\FileUpload\ErrorFileUpload;

class UserController
{
    public function create(Request $request, Response $response): Response
    {
        $injector = new Injector();
        $inputQuery = new InputQuery($injector);
        
        $method = new \ReflectionMethod($this, 'handleUserCreation');
        $args = $inputQuery->getArguments($method, $request->getParsedBody() ?? []);
        
        $result = $this->handleUserCreation(...$args);
        
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    public function handleUserCreation(
        #[Input] string $name,
        #[Input] string $email,
        #[InputFile] FileUpload|ErrorFileUpload|null $avatar = null,
    ): array {
        if ($avatar instanceof ErrorFileUpload) {
            throw new \InvalidArgumentException($avatar->message);
        }
        
        $avatarPath = null;
        if ($avatar instanceof FileUpload) {
            $avatarPath = $avatar->move(__DIR__ . '/../../uploads');
        }
        
        return [
            'success' => true,
            'user' => ['name' => $name, 'email' => $email],
            'avatar' => $avatarPath
        ];
    }
}
```

## Testing Examples

### PHPUnit Test for Framework Integration

```php
// tests/Integration/UserControllerTest.php
<?php

use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use Ray\InputQuery\InputQuery;
use Koriym\FileUpload\FileUpload;

class UserControllerTest extends TestCase
{
    private InputQuery $inputQuery;
    
    protected function setUp(): void
    {
        $this->inputQuery = new InputQuery(new Injector());
    }
    
    public function testUserCreationWithFileUpload(): void
    {
        $controller = new UserController();
        $method = new \ReflectionMethod($controller, 'handleUserCreation');
        
        $mockAvatar = FileUpload::fromFile(__DIR__ . '/fixtures/test.jpg');
        
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'avatar' => $mockAvatar
        ];
        
        $args = $this->inputQuery->getArguments($method, $data);
        $result = $method->invokeArgs($controller, $args);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('John Doe', $result['user']['name']);
    }
}
```

## Common Patterns

### 1. Method Reflection Pattern

```php
$method = new \ReflectionMethod($controller, 'methodName');
$args = $inputQuery->getArguments($method, $requestData);
$result = $method->invokeArgs($controller, $args);
```

### 2. Direct Object Creation Pattern

```php
$input = $inputQuery->create(UserInput::class, $requestData);
$result = $controller->handleUser($input);
```

### 3. Spread Operator Pattern

```php
$args = $inputQuery->getArguments($method, $requestData);
$result = $controller->methodName(...$args);
```

## Framework-Specific Considerations

### Laravel
- Use `$request->all()` for form data
- Leverage Laravel's service container for InputQuery injection
- Handle file uploads with Laravel's storage system

### Symfony
- Use `$request->request->all()` for POST data
- Configure services in `services.yaml`
- Integrate with Symfony's file handling

### CakePHP
- Use `$this->request->getData()` for request data
- Follow CakePHP's controller conventions
- Use CakePHP's file upload helpers

### BEAR.Sunday
- Ray.InputQuery works natively with BEAR.Sunday
- Use directly in resource methods
- Leverage BEAR's built-in DI container

### Slim
- Use `$request->getParsedBody()` for form data
- Handle JSON responses appropriately
- Configure DI container for InputQuery

## Best Practices

1. **Validation First**: Always check for `ErrorFileUpload` instances before processing
2. **Type Safety**: Leverage union types for proper error handling
3. **DI Integration**: Use framework-specific DI containers when possible
4. **Testing**: Create mock `FileUpload` objects for unit tests
5. **Error Handling**: Implement consistent error responses across frameworks

---

> **Note**: This document was generated with AI assistance. If you find any errors or have suggestions for improvements, please feel free to open an issue or submit a pull request. We welcome community feedback to make this documentation more accurate and helpful.
