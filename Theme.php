<?php namespace Laradic\Themes;

use Closure;
use File;
use Laradic\Themes\Contracts\ThemeFactory as ThemeFactoryContract;
use Illuminate\Contracts\Events\Dispatcher;
use Laradic\Themes\Exceptions\ThemesConfigurationException;
use Stringy\Stringy;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class Theme
{

    /**
     * @var \Laradic\Themes\ThemeFactory
     */
    protected $themes;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $parentSlug;

    /**
     * @var Theme
     */
    protected $parentTheme;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $slug;

    protected $dispatcher;

    public function __construct(ThemeFactoryContract $themes, Dispatcher $dispatcher, $path)
    {
        $this->themes     = $themes;
        $this->path       = $path;
        $this->dispatcher = $dispatcher;

        if ( ! File::exists($path . '/theme.php') )
        {
            throw new FileNotFoundException("Error while loading theme, could not find " . $path . '/theme.php');
        }

        $this->config = include($path . '/theme.php');

        $this->name       = $this->config['name'];
        $this->slug       = $this->config['slug'];
        $this->parentSlug = $this->config['parent'];
        if ( isset($this->parentSlug) )
        {
            $this->parentTheme = $this->themes->resolveTheme($this->parentSlug);
        }


        if ( isset($this->config['register']) && $this->config['register'] instanceof Closure )
        {
            $this->config['register']($this->themes->getApplication(), $this);
        }
    }

    public function asset($path)
    {
        $filePath = Stringy::create($this->path . '/' . $this->themes->getPath('assets'));
        if ( $filePath->startsWith(public_path()) == false )
        {
            $message = "Invalid themes.php configuration. Theme path [{$this->path}] not inside the public directory";
            throw new ThemesConfigurationException($message);
        }
        $urlPath = $filePath->removeLeft(public_path());
    }

    /**
     * getCascadedPath
     *
     * @param string|null $cascadeType namespaces, packages or null
     * @param string|null $cascadeName the namespace, package or nulll
     * @param string|null $pathType    views, assets or null
     * @return string the path
     */
    public function getCascadedPath($cascadeType = null, $cascadeName = null, $pathType = null)
    {
        $path = $this->path . '/' . $this->themes->getPath($cascadeType);

        if ( ! is_null($cascadeName) )
        {
            $path .= '/' . $cascadeName;
        }

        if ( ! is_null($pathType) )
        {
            $path .= '/' . $this->themes->getPath($pathType);
        }

        return $path;
    }

    public function getPath($for = null)
    {
        if ( is_null($for) )
        {
            return $this->path;
        }
        else
        {
            return $this->path . '/' . $this->themes->getPath($for);
        }
    }

    protected $booted = false;

    public function boot()
    {
        if ( $this->booted )
        {
            return;
        }

        $this->dispatcher->fire('booting theme: ', [$this]);

        if ( isset($this->config['boot']) && $this->config['boot'] instanceof Closure )
        {
            $this->config['boot']($this->themes->getApplication(), $this);
        }


        $this->booted = true;
    }


    //
    /* SIMPLE GETTERS/SETTERS */
    //
    public function getConfig()
    {
        return $this->config;
    }

    public function getParentTheme()
    {
        return $this->parentTheme;
    }

    public function getParentSlug()
    {
        return $this->parentSlug;
    }

    public function getThemes()
    {
        return $this->themes;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getSlug()
    {
        return $this->slug;
    }
}