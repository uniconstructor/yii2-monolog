<?php

namespace uniconstructor\yii2\monolog;

use Monolog\Handler\HandlerInterface;
use Monolog\Logger as MonologLogger;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Yii;
use yii\base\Component;
use yii\log\Logger as YiiLogger;

/**
 * Yii2 Monolog Logger
 * ===================
 *
 * This component allows to replace a default Yii2 logger with a {@link https://github.com/Seldaek/monolog Monolog}.
 * It supports all the features of Monolog and keeps full compatibility with Yii2 logger.
 *
 * _Component usage example:_
 * ```
 * return [
 *      ...
 *      'components' => [
 *          ...
 *          'logger' => [
 *              'class' => '\uniconstructor\yii2\monolog\Logger',
 *          ],
 *          ...
 *      ],
 *      ...
 * ];
 * ```
 *
 * @mixin MonologLogger
 *
 * @link      http://uniconstructor.github.io/yii2-monolog GitHub repo.
 * @license   https://github.com/uniconstructor/yii2-monolog/blob/master/LICENSE.md MIT
 *
 * @author    Ivan Koptiev <ivan.koptiev@codex.systems>
 */
class Logger extends Component implements LoggerInterface
{
    /**
     * @var string $name Monolog channel name.
     * Yii application name (Yii::$app->name) will be used by default.
     */
    public $name;

    /**
     * @var HandlerInterface[] $handlers Optional stack of handlers, the first one in the array is called first, etc.
     */
    public $handlers = [];

    /**
     * @var callable[] $processors Optional array of processors
     */
    public $processors = [];

    /**
     * @var MonologLogger $_monolog Monolog instance.
     */
    private $_monolog;

    /**
     * Calls the named method which is not a class method.
     *
     * This method will check whether PSR-3 or Yii2 format is used and will execute the corresponding method.
     *
     * @param string $name The method name.
     * @param array $arguments Method arguments.
     *
     * @return mixed The method return value.
     */
    public function __call($name, array $arguments)
    {
        // Intercept calls to the "log()" method
        if ($name === 'log' && !empty($arguments)) {

            // PSR-3 format
            $reflection = new ReflectionClass(YiiLogger::className());
            if (
                in_array($arguments[0], array_keys($this->_monolog->getLevels()))
                && !in_array($arguments[1], array_keys($reflection->getConstants()))
                && (
                    !isset($arguments[3])
                    || (isset($arguments[3]) && is_array($arguments[3]))
                )
            ) {
                return call_user_func_array([$this, 'log'], $arguments);
            }

            // Yii2 format
            return call_user_func_array([$this, 'yiiLog'], $arguments);
        }

        // Execute Monolog methods, if they exists
        if (method_exists($this->_monolog, $name) && !method_exists($this, $name)) {
            return call_user_func_array([$this->_monolog, $name], $arguments);
        }

        return parent::__call($name, $arguments);
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // Default configuration
        if (empty($this->name)) {
            $this->name = Yii::$app->name;
        }

        $handlersConfig = [];
        foreach ($this->handlers as $handler) {
            if ($handler instanceof HandlerInterface) {
                $handlersConfig[] = $handler;
            } else if (is_array($handler)) {
                $handlersConfig[] = Yii::createObject($handler);
            }
        }

        // Create a logger
        $this->_monolog = new MonologLogger($this->name, $this->handlers, $this->processors);
    }

    /**
     * @inheritdoc
     * @return boolean Whether the record has been processed.
     */
    public function emergency($message, array $context = [])
    {
        return $this->_monolog->emergency($message, $context);
    }

    /**
     * @inheritdoc
     * @return boolean Whether the record has been processed.
     */
    public function alert($message, array $context = [])
    {
        return $this->_monolog->alert($message, $context);
    }

    /**
     * @inheritdoc
     * @return boolean Whether the record has been processed.
     */
    public function critical($message, array $context = [])
    {
        return $this->_monolog->critical($message, $context);
    }

    /**
     * @inheritdoc
     * @return boolean Whether the record has been processed.
     */
    public function error($message, array $context = [])
    {
        return $this->_monolog->error($message, $context);
    }

    /**
     * @inheritdoc
     * @return boolean Whether the record has been processed.
     */
    public function warning($message, array $context = [])
    {
        return $this->_monolog->warning($message, $context);
    }

    /**
     * @inheritdoc
     * @return boolean Whether the record has been processed.
     */
    public function notice($message, array $context = [])
    {
        return $this->_monolog->notice($message, $context);
    }

    /**
     * @inheritdoc
     * @return boolean Whether the record has been processed.
     */
    public function info($message, array $context = [])
    {
        return $this->_monolog->info($message, $context);
    }

    /**
     * @inheritdoc
     * @return boolean Whether the record has been processed.
     */
    public function debug($message, array $context = [])
    {
        return $this->_monolog->debug($message, $context);
    }

    /**
     * @inheritdoc
     * @return boolean Whether the record has been processed.
     */
    public function log($level, $message, array $context = [])
    {
        return $this->_monolog->log($level, $message, $context);
    }

    /**
     * Logs a message with the given type and category.
     *
     * @param string $message the message to be logged.
     * @param integer $level the level of the message. This must be one of the following:
     * `Logger::LEVEL_ERROR`, `Logger::LEVEL_WARNING`, `Logger::LEVEL_INFO`, `Logger::LEVEL_TRACE`,
     * `Logger::LEVEL_PROFILE_BEGIN`, `Logger::LEVEL_PROFILE_END`.
     * @param string $category the category of the message.
     *
     * @return boolean Whether the record has been processed.
     */
    public function yiiLog($message, $level, $category = 'application')
    {
        return $this->_monolog->log($level, $message, [
            'category' => $category,
        ]);
    }
}
