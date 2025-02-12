<?php

namespace Igniter\Tests\Flame\Support;

use Igniter\Flame\Currency\Currency;
use Igniter\Flame\Currency\Facades\Currency as CurrencyFacade;
use Igniter\Flame\Html\FormBuilder;
use Igniter\Flame\Pagic\Environment;
use Igniter\Main\Classes\MainController;
use Igniter\Main\Classes\MediaLibrary;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Template\Page;
use Igniter\System\Facades\Assets;
use Igniter\System\Facades\Country;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\HtmlString;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

it('returns current url', function() {
    $urlGenerator = mock(UrlGenerator::class);
    app()->instance(UrlGenerator::class, $urlGenerator);
    $urlGenerator->shouldReceive('current')->andReturn('http://example.com/current');
    expect(current_url())->toBe('http://example.com/current');
});

it('returns uploads path with given subpath', function() {
    app()->instance('path.uploads', '/var/www/uploads');
    expect(uploads_path('images'))->toBe('/var/www/uploads/images');
});

it('returns theme url with given uri', function() {
    config()->set('igniter-system.themesDir', 'themes');
    expect(theme_url('my-theme/style.css'))->toBe(asset('themes/my-theme/style.css'));
});

it('returns theme path with given path', function() {
    app()->instance('path.themes', '/var/www/themes');
    expect(theme_path('my-theme/style.css'))->toBe('/var/www/themes/my-theme/style.css');
});

it('returns temp path', function() {
    app()->instance('path.temp', '/var/www/temp');
    expect(temp_path('file.tmp'))->toBe('/var/www/temp/file.tmp');
});

it('returns referrer url', function() {
    $urlGenerator = mock(UrlGenerator::class);
    app()->instance(UrlGenerator::class, $urlGenerator);
    $urlGenerator->shouldReceive('previous')->andReturn('http://example.com/previous');
    expect(referrer_url())->toBe('http://example.com/previous');
});

it('returns system setting & params value with default', function() {
    setting()->set('site_name', 'My Site');
    expect(setting('site_name', 'default'))->toBe('My Site')
        ->and(setting('non-existence-key', 'default'))->toBe('default');

    setting()->setPref('param_key', 'value');
    expect(params('param_key', 'default'))->toBe('value')
        ->and(params('non-existence-key', 'default'))->toBe('default');
});

it('parses values in string', function() {
    $columns = ['name' => 'John', 'age' => 30];
    $string = 'Name: {name}, Age: {age}';
    expect(parse_values($columns, $string))->toBe('Name: John, Age: 30');
});

it('returns request value with default', function() {
    request()->merge(['name' => 'John']);
    request()->request->add(['post' => 'John']);
    request()->query->add(['get' => 'John']);
    expect(input())->toBe(['name' => 'John', 'get' => 'John'])
        ->and(input('name', 'default'))->toBe('John')
        ->and(post())->toBe(['post' => 'John'])
        ->and(post('post', 'default'))->toBe('John')
        ->and(get())->toBe(['name' => 'John', 'get' => 'John'])
        ->and(get('get', 'default'))->toBe('John');
});

it('translates given key', function() {
    Lang::shouldReceive('get')->with('welcome', [], null, true)->andReturn('Welcome');
    expect(lang('welcome'))->toBe('Welcome');
});

it('normalizes class name and uri with leading slash', function() {
    expect(get_class_id('App\\Models\\User'))->toBe('app_models_user')
        ->and(normalize_class_name('\\App\\Models\\User'))->toBe('\\App\\Models\\User')
        ->and(normalize_uri('path/to/resource'))->toBe('/path/to/resource')
        ->and(normalize_uri(''))->toBe('/');
});

it('converts amount to currency', function() {
    CurrencyFacade::shouldReceive('convert')->with(100, 'USD', 'EUR', true)->andReturn('85.00 EUR');
    CurrencyFacade::shouldReceive('format')->with(100, 'USD', true)->andReturn('$100.00');
    CurrencyFacade::shouldReceive('formatToJson')->with(100, 'USD')->andReturn(['amount' => 100, 'currency' => 'USD']);
    expect(currency(100, 'USD', 'EUR'))->toBe('85.00 EUR')
        ->and(currency())->toBeInstanceOf(Currency::class)
        ->and(currency_format(100, 'USD'))->toBe('$100.00')
        ->and(currency_json(100, 'USD'))->toBe(['amount' => 100, 'currency' => 'USD']);
});

it('returns flash message', function() {
    $flashBag = mock(\Igniter\Flame\Flash\FlashBag::class);
    app()->instance('flash', $flashBag);
    $flashBag->shouldReceive('message')->with('Test message', 'info')->andReturn($flashBag);
    $result = flash('Test message');
    expect($result)->toBe($flashBag);
});

it('undots array keys', function() {
    $array = ['user.name' => 'John', 'user.age' => 30];
    expect(array_undot($array))->toBe(['user' => ['name' => 'John', 'age' => 30]]);
});

it('returns page url with parameters', function() {
    expect(controller())->toBeInstanceOf(MainController::class)
        ->and(page_url('nested-page', ['levelOne' => 'one']))->toBe('http://localhost/nested-page/one')
        ->and(restaurant_url('nested-page', ['levelOne' => 'one']))->toBe('http://localhost/nested-page/one')
        ->and(admin_url('dashboard'))->toBe('http://localhost/admin/dashboard');
});

it('returns media url with path', function() {
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class));
    $mediaLibrary->shouldReceive('getMediaUrl')->with('path/to/file')->andReturn('http://example.com/media/path/to/file');
    $result = media_url('path/to/file');
    expect($result)->toBe('http://example.com/media/path/to/file');
});

it('returns media thumbnail with options', function() {
    config()->set('igniter-system.assets.media.folder', 'data');
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class));
    $mediaLibrary->shouldReceive('getMediaThumb')->with('path/to/image.jpg', [
        'width' => 100,
        'height' => 200,
    ])->andReturn('resized/image.jpg');

    expect(media_thumb('path/to/image.jpg', ['width' => 100, 'height' => 200]))
        ->toContain('resized/image.jpg');
});

it('strips class basename with chop', function() {
    expect(strip_class_basename('App\\Models\\Users_model', null))->toBe('Users_model')
        ->and(strip_class_basename('App\\Models\\Users_model', '_model'))->toBe('Users')
        ->and(strip_class_basename('App\\Models\\User', '_model'))->toBe('User');
});

it('converts mysql date format to php date format', function() {
    expect(parse_date_format('%Y-%m-%d'))->toBe('Y-m-d')
        ->and(convert_php_to_moment_js_format('Y-m-d'))->toBe('YYYY-MM-DD')
        ->and(mdate(strtotime('2023-01-01')))->toBe('01 Jan 2023')
        ->and(mdate())->toBeNull()
        ->and(mdate('%Y-%m-%d', ''))->toBe(date('Y-m-d'))
        ->and(mdate('%Y-%m-%d', strtotime('2023-01-01')))->toBe('2023-01-01');
});

it('returns day elapsed with full format', function() {
    $this->travelTo('2023-10-10 00:00:00');
    expect(day_elapsed('2023-10-10 00:00:00'))->toBe('Today at 12:00 am')
        ->and(day_elapsed('2023-10-10 00:00:00', false))->toBe('Today')
        ->and(day_elapsed('2023-10-09 00:00:00'))->toBe('Yesterday at 12:00 am')
        ->and(day_elapsed('2023-10-09 00:00:00', false))->toBe('Yesterday')
        ->and(day_elapsed('2023-10-11 00:00:00'))->toBe('Tomorrow at 12:00 am')
        ->and(day_elapsed('2023-10-11 00:00:00', false))->toBe('Tomorrow')
        ->and(day_elapsed('2023-01-01 00:00:00'))->toBe('01 Jan 2023 at 12:00 am');
    $this->travelBack();
});

it('returns time elapsed and range', function() {
    $this->travelTo('2023-10-01 00:00:00');
    expect(time_elapsed('2023-01-01 00:00:00'))->toBe('9 months ago')
        ->and(time_range('', '', ''))->toBeNull()
        ->and(time_range('2023-01-01 00:00:00', '2023-01-01 01:00:00', '15 mins'))
        ->toBe(['00:00', '00:15', '00:30', '00:45', '01:00']);
    $this->travelBack();
});

it('makes carbon instance from timestamp', function() {
    $this->travelTo('2023-01-01 00:00:00');
    expect(make_carbon(now())->toDateTimeString())->toBe('2023-01-01 00:00:00')
        ->and(make_carbon(new \DateTime('2023-01-01 00:00:00'))->toDateTimeString())->toBe('2023-01-01 00:00:00')
        ->and(make_carbon('2023-01-01')->toDateTimeString())->toBe('2023-01-01 00:00:00')
        ->and(make_carbon(1672531200)->toDateTimeString())->toBe('2023-01-01 00:00:00')
        ->and(fn() => make_carbon('invalid'))->toThrow('Invalid date value supplied to DateTime helper.');
    $this->travelBack();
});

it('returns true for single location mode', function() {
    config()->set('igniter-system.locationMode', \Igniter\Local\Models\Location::LOCATION_CONTEXT_SINGLE);
    $result = is_single_location();
    expect($result)->toBeTrue();
});

it('logs message with info level', function() {
    Log::shouldReceive('info')->once()->with('Test message');
    log_message('info', 'Test message');
});

it('traces log with info level', function() {
    Log::shouldReceive('info')->twice();
    Log::shouldReceive('error')->once();
    traceLog('Test trace message', ['key' => 'value']);
    traceLog(new \Exception());
});

it('sorts array by key', function() {
    $array = [
        ['priority' => 2, 'name' => 'B'],
        ['priority' => 1, 'name' => 'A'],
    ];
    $result = sort_array($array);
    expect($result)->toBe([
        ['priority' => 1, 'name' => 'A'],
        ['priority' => 2, 'name' => 'B'],
    ]);
});

it('converts input name to id/array', function() {
    $result = name_to_id('user[location][city]');
    expect($result)->toBe('user-location-city')
        ->and(name_to_array('user[location][city]'))->toBe(['user', 'location', 'city'])
        ->and(name_to_dot_string('user[location][city]'))->toBe('user.location.city');
});

it('returns true for language key', function() {
    expect(is_lang_key('namespace::key'))->toBeTrue()
        ->and(is_lang_key('lang:namespace::key'))->toBeTrue()
        ->and(is_lang_key('lang'))->toBeFalse();
});

it('generates extension icon with url', function() {
    $expected = [
        'class' => 'fa fa-plug',
        'color' => '',
        'image' => null,
        'backgroundColor' => '',
        'backgroundImage' => null,
        'url' => 'http://example.com/icon.png',
        'styles' => '',
    ];
    expect(generate_extension_icon('http://example.com/icon.png'))->toBe($expected)
        ->and(generate_extension_icon('fa-plug'))->toBe(array_except($expected, 'url'))
        ->and(generate_extension_icon([
            'class' => 'fa fa-puzzle',
            'color' => 'red',
            'image' => 'path/to/image.png',
            'backgroundColor' => '#fff',
            'backgroundImage' => ['image/png', 'cGF0aC90by9pbWFnZS5wbmc='],
        ]))->toBe([
            'class' => 'fa fa-puzzle',
            'color' => 'red',
            'image' => 'path/to/image.png',
            'backgroundColor' => '#fff',
            'backgroundImage' => ['image/png', 'cGF0aC90by9pbWFnZS5wbmc='],
            'styles' => 'color:red; background-color:#fff; background-image:url(\'data:image/png;base64,cGF0aC90by9pbWFnZS5wbmc=\');',
        ]);
});

it('replaces array key', function() {
    $array = ['oldKey' => 'value'];
    $result = array_replace_key($array, 'oldKey', 'newKey');
    expect($result)->toBe(['newKey' => 'value']);
});

it('inserts array after key', function() {
    $array = ['key1' => 'value1', 'key2' => 'value2'];
    $result = array_insert_after($array, 'key1', ['key3' => 'value3']);
    expect($result)->toBe(['key1' => 'value1', 'key3' => 'value3', 'key2' => 'value2']);
});

it('merges arrays deeply', function() {
    $array1 = ['key1' => ['subkey1' => 'value1']];
    $array2 = ['key1' => ['subkey2' => 'value2', 'value3']];
    $result = array_merge_deep($array1, $array2);
    expect($result)->toBe(['key1' => ['subkey1' => 'value1', 'subkey2' => 'value2', 'value3']]);
});

//
// Assets Helper
//

it('returns metas html tags', function() {
    $meta = ['name' => 'description', 'content' => 'Test'];
    Assets::shouldReceive('collection->addMeta')->once()->with($meta);
    set_meta($meta);

    Assets::shouldReceive('getMetas')->once()->andReturn('<meta name="description" content="Test">');
    $result = get_metas();
    expect($result)->toBe('<meta name="description" content="Test">');
});

it('returns favicon html tag', function() {
    $href = 'favicon.ico';
    Assets::shouldReceive('addFavIcon')->once()->with($href);
    set_favicon($href);

    Assets::shouldReceive('getFavIcon')->once()->andReturn('<link rel="icon" href="favicon.ico">');
    $result = get_favicon();
    expect($result)->toBe('<link rel="icon" href="favicon.ico">');
});

it('returns multiple stylesheet html tags', function() {
    $href = 'style.css';
    Assets::shouldReceive('addCss')->once()->with($href);
    set_style_tag($href);

    $tags = ['style1.css', 'style2.css'];
    Assets::shouldReceive('addTags')->once()->with(['css' => $tags]);
    set_style_tags($tags);

    Assets::shouldReceive('getCss')->once()->andReturn('<link rel="stylesheet" href="style.css">');
    $result = get_style_tags();
    expect($result)->toBe('<link rel="stylesheet" href="style.css">');
});

it('returns multiple scripts html tags', function() {
    $href = 'script.js';
    Assets::shouldReceive('addJs')->once()->with($href);
    set_script_tag($href);

    $tags = ['script1.js', 'script2.js'];
    Assets::shouldReceive('addTags')->once()->with(['js' => $tags]);
    set_script_tags($tags);

    Assets::shouldReceive('getJs')->once()->andReturn('<script src="script.js"></script>');
    $result = get_script_tags();
    expect($result)->toBe('<script src="script.js"></script>');
});

it('combines assets of given type', function() {
    $type = 'css';
    $assets = ['style1.css', 'style2.css'];
    Assets::shouldReceive('combine')->once()->with($type, $assets)->andReturn('combined.css');
    $result = combine($type, $assets);
    expect($result)->toBe('combined.css');
});

//
// Country Helper
//

it('formats address with line breaks', function() {
    $address = ['street' => '123 Main St', 'city' => 'Anytown', 'country' => 'USA'];
    Country::shouldReceive('addressFormat')->with($address, true)->andReturn("123 Main St\nAnytown\nUSA");
    $result = format_address($address, true);
    expect($result)->toBe("123 Main St\nAnytown\nUSA");
});

it('returns list of countries with default columns', function() {
    $countries = collect([
        ['country_id' => 1, 'country_name' => 'USA'],
        ['country_id' => 2, 'country_name' => 'Canada'],
    ]);
    Country::shouldReceive('listAll')->with('country_name', 'country_id')->andReturn($countries);
    expect(countries())->toBe($countries);
});

//
// Form Helper
//

it('opens form with action url', function() {
    app()->instance(FormBuilder::class, $formBuilder = mock(FormBuilder::class));
    $formBuilder->shouldReceive('open')->with(['url' => 'test/action'])->andReturn('<form>');
    expect(form_open('test/action'))->toBe('<form>');
});

it('opens form with attributes', function() {
    app()->instance(FormBuilder::class, $formBuilder = mock(FormBuilder::class));
    $formBuilder->shouldReceive('open')->with(['method' => 'POST', 'handler' => 'onTest'])->andReturn('<form>');
    $formBuilder->shouldReceive('hidden')->andReturn('<input type="hidden">');
    expect(form_open(['method' => 'POST', 'handler' => 'onTest']))->toBe('<form><input type="hidden">');
});

it('opens multipart form', function() {
    app()->instance(FormBuilder::class, $formBuilder = mock(FormBuilder::class));
    $formBuilder->shouldReceive('open')->with([
        'url' => 'test/action',
        'enctype' => 'multipart/form-data',
    ])->andReturn('<form>');
    expect(form_open_multipart('test/action'))->toBe('<form>');
});

it('closes form with extra content', function() {
    app()->instance(FormBuilder::class, $formBuilder = mock(FormBuilder::class));
    $formBuilder->shouldReceive('close')->andReturn('</form>');
    expect(form_close('extra content'))->toBe('</form>extra content');
});

it('sets form value from POST data', function() {
    app()->instance(FormBuilder::class, $formBuilder = mock(FormBuilder::class));
    $formBuilder->shouldReceive('getValueAttribute')->with('field', 'default')->andReturn('value');
    expect(set_value('field', 'default'))->toBe('value');
});

it('sets select option as selected', function() {
    app()->instance(FormBuilder::class, $formBuilder = mock(FormBuilder::class));
    $formBuilder->shouldReceive('getValueAttribute')->with('null_field', false)->andReturnNull();
    expect(set_select('null_field', 'default', true))->toBe(' selected="selected"');

    $formBuilder->shouldReceive('getValueAttribute')->with('field', false)->andReturn('value');
    $formBuilder->shouldReceive('getValueAttribute')->with('array_field', false)->andReturn(['value', 'another-value']);
    $formBuilder->shouldReceive('getValueAttribute')->with('another_field', false)->andReturn('invalid-value');
    expect(set_select('field', 'value'))->toBe(' selected="selected"')
        ->and(set_select('array_field', 'value'))->toBe(' selected="selected"')
        ->and(set_select('array_field', 'invalid-value'))->toBe('');
});

it('sets checkbox as checked', function() {
    app()->instance(FormBuilder::class, $formBuilder = mock(FormBuilder::class));
    $formBuilder->shouldReceive('getValueAttribute')->with('null-value-field', false)->andReturnNull();
    $formBuilder->shouldReceive('getValueAttribute')->with('field', false)->andReturn('value');
    $formBuilder->shouldReceive('getValueAttribute')->with('array_field', false)->andReturn(['value', 'another-value']);
    $formBuilder->shouldReceive('getValueAttribute')->with('another_field', false)->andReturn('invalid-value');
    expect(set_checkbox('null-value-field', 'value'))->toBe('')
        ->and(set_checkbox('field', 'value'))->toBe(' checked="checked"')
        ->and(set_checkbox('array_field', 'value'))->toBe(' checked="checked"')
        ->and(set_checkbox('array_field', 'invalid-value'))->toBe('');
});

it('sets radio button as checked', function() {
    app()->instance(FormBuilder::class, $formBuilder = mock(FormBuilder::class));
    $formBuilder->shouldReceive('getValueAttribute')->with('null-value-field', false)->andReturnNull();
    $formBuilder->shouldReceive('getValueAttribute')->with('field', false)->andReturn('value');
    $formBuilder->shouldReceive('getValueAttribute')->with('array_field', false)->andReturn(['value', 'another-value']);
    $formBuilder->shouldReceive('getValueAttribute')->with('another_field', false)->andReturn('invalid-value');
    expect(set_radio('null-value-field', 'value'))->toBe('')
        ->and(set_radio('field', 'value'))->toBe(' checked="checked"')
        ->and(set_radio('array_field', 'value'))->toBe(' checked="checked"')
        ->and(set_radio('array_field', 'invalid-value'))->toBe('');
});

it('returns form error for field', function() {
    $errors = mock(ViewErrorBag::class);
    $errors->shouldReceive('getBag')->with('default')->andReturn($errors);
    $errors->shouldReceive('has')->with('field')->andReturn(true);
    $errors->shouldReceive('has')->with('non-existence-field')->andReturn(false);
    $errors->shouldReceive('first')->with('field')->andReturn('error message');
    Session::shouldReceive('has')->with('errors')->andReturn(true);
    Session::shouldReceive('get')->with('errors')->andReturn($errors);
    expect(form_error('field', '<p>', '</p>'))->toBe('<p>error message</p>')
        ->and(form_error())->toBeInstanceOf(ViewErrorBag::class)
        ->and(form_error('non-existence-field'))->toBeNull();
});

it('checks if form has error for field', function() {
    $errors = mock(ViewErrorBag::class);
    $errors->shouldReceive('getBag')->with('default')->andReturn($errors);
    $errors->shouldReceive('has')->with('field')->andReturn(true);
    Session::shouldReceive('has')->with('errors')->andReturn(true, false);
    Session::shouldReceive('get')->with('errors')->andReturn($errors, []);
    expect(has_form_error('field'))->toBeTrue()
        ->and(has_form_error())->toBeInstanceOf(MessageBag::class);
});

//
// Template helper
//

it('renders pagic template with variables', function() {
    app()->instance('pagic', $pagic = mock(Environment::class));
    $pagic->shouldReceive('render')->with('tests.admin::test', ['var' => 'value'])->andReturn('rendered content');
    expect(pagic())->toBe($pagic)
        ->and(pagic('tests.admin::test', ['var' => 'value']))->toBe('rendered content');
});

it('returns page template content', function() {
    $page = mock(Page::class)->makePartial();
    $mainController = new MainController();
    $mainController->runPage($page);
    expect(page())->toBeString();
});

it('loads content template with data', function() {
    expect(content('test-content', ['key' => 'value']))->toContain('This is a test content');
});

it('loads partial template with data', function() {
    expect(partial('test-partial', ['key' => 'value']))->toContain('This is a test partial content');
});

it('checks if component is loaded', function() {
    expect(has_component('component'))->toBeFalse();
});

it('renders component with parameters', function() {
    $page = Page::resolveRouteBinding('components');
    controller()->runPage($page);
    expect(component('testComponent', ['param' => 'value']))->toContain('This is a test component partial content');
});

it('returns page title', function() {
    $page = Page::resolveRouteBinding('components');
    controller()->runPage($page);
    expect(get_title())->toBe('Components');
});

it('returns html string', function() {
    $result = html('<p>Test</p>');
    expect($result)->toBeInstanceOf(HtmlString::class)
        ->and($result->toHtml())->toBe('<p>Test</p>');
});

//
// Theme Helper
//

it('returns active theme code', function() {
    app()->instance(ThemeManager::class, $themeManager = mock(ThemeManager::class));
    $themeManager->shouldReceive('getActiveThemeCode')->andReturn(null, 'active-theme-code');
    expect(active_theme())->toBeNull()
        ->and(active_theme())->toBe('active-theme-code');
});

it('returns parent theme code', function() {
    app()->instance(ThemeManager::class, $themeManager = mock(ThemeManager::class));
    $themeManager->shouldReceive('findParentCode')->with('child-theme')->andReturn('parent-theme-code');
    expect(parent_theme('child-theme'))->toBe('parent-theme-code');

    $themeManager->shouldReceive('findParentCode')->with('nonexistent-theme')->andReturn(null);
    expect(parent_theme('nonexistent-theme'))->toBeNull();
});
