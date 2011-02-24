<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2008 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Cache class that stores cached content in memcache.
 *
 * @package    symfony
 * @subpackage cache
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author     David Ashwood <dasher@inspiredthinking.co.uk>
 * @author     Toni Uebernickel <tuebernickel@gmail.com>
 * @version    SVN: $Id:  $
 */
class sfMemcachedSaslCache extends sfCache
{
  protected
    $memcached = null;

  /**
   * Initializes this sfCache instance.
   *
   * If SASL auth is available, it will be configured.
   *
   * Available options:
   *
   * * memcached: A memcached object (optional)
   *
   * * host:       The default host (default to localhost)
   * * port:       The port for the default server (default to 11211)
   * * weight: true if the connection must be persistent, false otherwise (true by default)
   * * username:   The username to use for SASL auth.
   * * password:   The password to use for SASL auth.
   *
   * * servers:    An array of additional servers (keys: host, port, weight)
   *
   * * see sfCache for options available for all drivers
   *
   * @see sfCache
   */
  public function initialize($options = array())
  {
    parent::initialize($options);

    if (!extension_loaded('memcached'))
    {
      throw new sfInitializationException('You must have memcached installed and enabled to use sfMemcachedSaslCache class.');
    }

    if ($this->getOption('memcached'))
    {
      $this->memcached = $this->getOption('memcached');
    }
    else
    {
      $this->memcached = new Memcached();

      if ($this->getOption('username') and $this->getOption('password'))
      {
        $this->configureSasl();
      }

      if ($this->getOption('servers'))
      {
        $this->memcached->addServers($this->getOption('servers'));
      }
      else
      {
        $this->memcached->addServer(
          $this->getOption('host', 'localhost'),
          $this->getOption('port', 11211),
          $this->getOption('weight', 100)
        );
      }
    }
  }

  /**
   * Configures the SASL auth.
   *
   * @return sfMemcachedSaslCache $this
   */
  protected function configureSasl()
  {
    if (method_exists($this->memcached, 'configureSasl'))
    {
      $this->memcached->configureSasl($this->getOption('username'), $this->getOption('password'));
    }

    return $this;
  }

  /**
   * @see sfCache
   */
  public function getBackend()
  {
    return $this->memcached;
  }

 /**
  * @see sfCache
  */
  public function get($key, $default = null)
  {
    $value = $this->memcached->get($this->getOption('prefix').$key);

    return false === $value ? $default : $value;
  }

  /**
   * @see sfCache
   */
  public function has($key)
  {
    return !(false === $this->memcached->get($this->getOption('prefix').$key));
  }

  /**
   * @see sfCache
   */
  public function set($key, $data, $lifetime = null)
  {
    $lifetime = null === $lifetime ? $this->getOption('lifetime') : $lifetime;

    // save metadata
    $this->setMetadata($key, $lifetime);

    // save key for removePattern()
    if ($this->getOption('storeCacheInfo', false))
    {
      $this->setCacheInfo($key);
    }

    if (false !== $this->memcached->replace($this->getOption('prefix').$key, $data, time() + $lifetime))
    {
      return true;
    }

    return $this->memcached->set($this->getOption('prefix').$key, $data, time() + $lifetime);
  }

  /**
   * @see sfCache
   */
  public function remove($key)
  {
    $this->memcached->delete($this->getOption('prefix').'_metadata'.self::SEPARATOR.$key);

    return $this->memcached->delete($this->getOption('prefix').$key);
  }

  /**
   * @see sfCache
   */
  public function clean($mode = sfCache::ALL)
  {
    if (sfCache::ALL === $mode)
    {
      return $this->memcached->flush();
    }
  }

  /**
   * @see sfCache
   */
  public function getLastModified($key)
  {
    if (false === ($retval = $this->getMetadata($key)))
    {
      return 0;
    }

    return $retval['lastModified'];
  }

  /**
   * @see sfCache
   */
  public function getTimeout($key)
  {
    if (false === ($retval = $this->getMetadata($key)))
    {
      return 0;
    }

    return $retval['timeout'];
  }

  /**
   * @see sfCache
   */
  public function removePattern($pattern)
  {
    if (!$this->getOption('storeCacheInfo', false))
    {
      throw new sfCacheException('To use the "removePattern" method, you must set the "storeCacheInfo" option to "true".');
    }

    $regexp = self::patternToRegexp($this->getOption('prefix').$pattern);

    foreach ($this->getCacheInfo() as $key)
    {
      if (preg_match($regexp, $key))
      {
        $this->memcached->delete($key);
      }
    }
  }

  /**
   * @see sfCache
   */
  public function getMany($keys)
  {
    $values = array();
    foreach ($this->memcached->get(array_map(create_function('$k', 'return "'.$this->getOption('prefix').'".$k;'), $keys)) as $key => $value)
    {
      $values[str_replace($this->getOption('prefix'), '', $key)] = $value;
    }

    return $values;
  }

  /**
   * Gets metadata about a key in the cache.
   *
   * @param string $key A cache key
   *
   * @return array An array of metadata information
   */
  protected function getMetadata($key)
  {
    return $this->memcached->get($this->getOption('prefix').'_metadata'.self::SEPARATOR.$key);
  }

  /**
   * Stores metadata about a key in the cache.
   *
   * @param string $key      A cache key
   * @param string $lifetime The lifetime
   */
  protected function setMetadata($key, $lifetime)
  {
    $this->memcached->set($this->getOption('prefix').'_metadata'.self::SEPARATOR.$key, array('lastModified' => time(), 'timeout' => time() + $lifetime),  $lifetime);
  }

  /**
   * Updates the cache information for the given cache key.
   *
   * @param string $key The cache key
   */
  protected function setCacheInfo($key)
  {
    $keys = $this->memcached->get($this->getOption('prefix').'_metadata');
    if (!is_array($keys))
    {
      $keys = array();
    }
    $keys[] = $this->getOption('prefix').$key;
    $this->memcached->set($this->getOption('prefix').'_metadata', $keys, 0);
  }

  /**
   * Gets cache information.
   */
  protected function getCacheInfo()
  {
    $keys = $this->memcached->get($this->getOption('prefix').'_metadata');
    if (!is_array($keys))
    {
      return array();
    }

    return $keys;
  }
}
