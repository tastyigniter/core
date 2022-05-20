<?php

namespace Igniter\System\Traits;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Str;
use Illuminate\View\ViewFinderInterface;

trait ViewMaker
{
    /**
     * @var array A list of variables to pass to the page.
     */
    public $vars = [];

    /**
     * @var array Specifies a path to the views directory. ex. ['package::view' => 'package']
     */
    public $viewPath;

    /**
     * @var array Specifies a path to the layout directory.
     */
    public $layoutPath;

    /**
     * @var array Specifies a path to the partials directory.
     */
    public $partialPath;

    /**
     * @var string Layout to use for the view.
     */
    public $layout;

    /**
     * @var bool Prevents the use of a layout.
     */
    public $suppressLayout = false;

    protected $viewFileExtension = '.blade.php';

    public function guessViewPath($view, $paths = [], $prefix = null)
    {
        if (!is_array($paths))
            $paths = [$paths];

        $guess = collect($paths)
            ->prepend($prefix, $view)
            ->reduce(function ($carry, $directory, $prefix) use ($view) {
                if (!is_null($carry)) {
                    return $carry;
                }

                $viewName = Str::after($view, $prefix.'::');

                if (view()->exists($view = $this->guessViewName($viewName, $directory))) {
                    return $view;
                }

                if (view()->exists($view = $this->guessViewName($viewName, $directory).'.index')) {
                    return $view;
                }
            });

        return $guess ?: $view;
    }

    public function guessViewName($name, $prefix = 'components.')
    {
        if ($prefix && !Str::endsWith($prefix, '.'))
            $prefix .= '.';

        $delimiter = ViewFinderInterface::HINT_PATH_DELIMITER;

        if (Str::contains($name, $delimiter)) {
            return Str::replaceFirst($delimiter, $delimiter.$prefix, $name);
        }

        return $prefix.$name;
    }

    /**
     * Loads a view with the name specified.
     * Applies layout if its name is provided by the parent object.
     * The view file must be situated in the views directory, and has the extension "htm" or "php".
     *
     * @param string $view Specifies the view name, without extension. Eg: "index".
     *
     * @return string
     */
    public function makeView($view)
    {
        $view = $this->guessViewPath(strtolower($view), $this->viewPath);

        $vars = $this->gatherViewData();

        return view($view, $vars)->render();
    }

    /**
     * Render a partial file contents located in the views or partial folder.
     *
     * @param string $partial The view to load.
     * @param array $vars Parameter variables to pass to the view.
     * @param string $prefix
     *
     * @return string Partial contents or false if not throwing an exception.
     */
    public function makePartial($partial, $vars = [], $prefix = '_partials')
    {
        $partial = $this->guessViewPath(strtolower($partial), $this->partialPath, $prefix);

        $vars = $this->gatherViewData($vars);

        if (isset($this->controller))
            $vars = array_merge($this->controller->vars, $vars);

        return view($partial, $vars)->render();
    }

    /**
     * Get the data bound to the view instance.
     *
     * @param $data
     * @return array
     */
    protected function gatherViewData($data = [])
    {
        $data = array_merge(['self' => $this], $this->vars, $data);

        return array_map(function ($value) {
            if ($value instanceof Renderable)
                return $value->render();

            return $value;
        }, $data);
    }
}
