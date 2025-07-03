# Framework Comparison

A comparison of HTTP input handling approaches across popular PHP frameworks.

## The Problem

All web frameworks face the same challenge: transforming flat HTTP data into structured, type-safe PHP objects.

## Native PHP

```php
// Controller
public function updateProfile()
{
    $name = $_POST['name'] ?? '';           // Manual extraction - no validation
    $email = $_POST['email'] ?? '';         // Manual extraction - no validation
    
    // Manual file handling with error checking
    $avatar = null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $avatar = $_FILES['avatar'];         // Raw array - no type safety
        // Manual validation: size, type, etc.
        if ($avatar['size'] > 2048000) {
            throw new Exception('File too large');
        }
    }
    
    $banner = null;
    if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
        $banner = $_FILES['banner'];         // Raw array - no type safety
    }
    
    // Manual validation for all fields
    if (empty($name)) {
        throw new Exception('Name is required');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email');
    }
}

// Framework integration
$result = $controller->updateProfile();
```

## Laravel

```php
// Request class
class extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string',        // String-based rules - no IDE support
            'email' => 'required|email',        // Typos not caught at compile time
            'avatar' => 'required|file|image|max:2048',  // Complex string parsing
            'banner' => 'nullable|file|image|max:2048',  // Runtime validation only
        ];
    }
}

// Controller
public function updateProfile(UpdateProfileRequest $request)
{
    $name = $request->name;      // Magic property - PHPStan sees as mixed
    $email = $request->email;    // Magic property - PHPStan sees as mixed
    $avatar = $request->file('avatar');  // Returns UploadedFile|null
    $banner = $request->file('banner');  // Returns UploadedFile|null
    
    // Manual file handling and null checks required
    if ($avatar) {
        $avatarPath = $avatar->store('avatars');
    }
}

// Framework integration
$request = UpdateProfileRequest::createFromGlobals();
$request->validate();
$result = $controller->updateProfile($request);
```

## Symfony

```php
// Form type
class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class)     // String field names - no IDE support
            ->add('email', EmailType::class)   // String field names - no IDE support
            ->add('avatar', FileType::class, [ // String field names - no IDE support
                'constraints' => [new File(['maxSize' => '2M'])]
            ])
            ->add('banner', FileType::class, ['required' => false]); // String field names - no IDE support
    }
}

// Controller
public function updateProfile(Request $request)
{
    $form = $this->createForm(ProfileType::class);
    $form->handleRequest($request);
    
    if ($form->isValid()) {
        $data = $form->getData();  // Returns mixed array - no IDE support
        $name = $data['name'];     // Array key unknown to IDE - no autocompletion
        $email = $data['email'];   // Array key unknown to IDE - no autocompletion
        $avatar = $form->get('avatar')->getData(); // String field names - no IDE support
    }
}

// Framework integration
$form = $this->createForm(ProfileType::class);
$form->handleRequest($request);
$data = $form->getData();
$result = $controller->updateProfile($data);
```

## Ray.InputQuery

```php
// Controller method
public function updateProfile(
    #[Input] string $name,
    #[Input] string $email,
    #[Input] FileUpload|ErrorFileUpload $avatar,
    #[Input] FileUpload|ErrorFileUpload|null $banner = null,
): void {
    // File objects are ready to use
    if ($avatar instanceof FileUpload) {
        $avatar->move('/path/to/avatars/' . $avatar->name);
    }
}

// Framework integration (4 lines)
$inputQuery = new InputQuery(new Injector());
$method = new ReflectionMethod($controller, 'updateProfile');
$args = $inputQuery->getArguments($method, $_POST);
$result = $method->invokeArgs($controller, $args);
```

## Key Differences

| Aspect | Native PHP | Laravel | Symfony | Ray.InputQuery |
|--------|------------|---------|---------|----------------|
| **Declaration** | Manual $_POST/$_FILES | Separate class | Separate class | Method signature |
| **Type Safety** | None | Runtime validation | Runtime validation | Compile-time types |
| **File Handling** | Raw arrays | Manual methods | Manual extraction | Automatic objects |
| **Boilerplate** | Very High | High | High | Minimal |
| **IDE Support** | None | Limited | Limited | Full type hints |

## Design Philosophy

**Native PHP**: Manual extraction and validation of HTTP data.

**Laravel/Symfony**: Validation-first approach with separate request classes.

**Ray.InputQuery**: Type-first approach using PHP's native type system and dependency injection.

The code speaks for itself.
