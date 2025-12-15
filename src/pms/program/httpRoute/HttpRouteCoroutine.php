<?php

namespace pms\program\httpRoute;

use pms\facade\Ctx;

class HttpRouteCoroutine
{

    public function pushResult(string $class, mixed $result): static
    {
        $name = static::class . "__result__";
        $resultRaw = Ctx::get($name,[]);
        $resultRaw[$class] = $result;
        Ctx::set($name, $resultRaw);
        return $this;
    }

    public function getResult(string $class): mixed
    {
        $name = static::class . "__result__";
        $resultRaw = Ctx::get($name,[]);
        return $resultRaw[$class] ?? null;
    }

    public function listener(string $event, callable $listener): static
    {
        $name = static::class . "__listener__";
        $resultRaw = Ctx::get($name,[]);
        if (!array_key_exists($event, $resultRaw)) {
            $resultRaw[$event] = [];
        }
        $resultRaw[$event][] = $listener;
        Ctx::set($name, $resultRaw);
        return $this;
    }

    public function trigger(string $event, array $data = []): static
    {
        $name = static::class . "__listener__";
        $resultRaw = Ctx::get($name,[]);
        if(!array_key_exists($event, $resultRaw)) {
            return $this;
        }
        foreach ($resultRaw[$event] as $listener) {
            $listener(...$data);
        }
        return $this;
    }

}