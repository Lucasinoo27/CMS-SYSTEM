<?php

if (!function_exists('authorize')) {
    /**
     * Authorize the given action or throw an exception.
     *
     * @param  mixed  $ability
     * @param  mixed  $arguments
     * @return \Illuminate\Auth\Access\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    function authorize($ability, $arguments = [])
    {
        if (is_callable($ability)) {
            $result = $ability(auth()->user());
            return $result === true ? true : abort(403);
        }
        
        return app(\Illuminate\Contracts\Auth\Access\Gate::class)->authorize($ability, $arguments);
    }
} 