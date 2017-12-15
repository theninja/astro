<?php

namespace Astro\API\Models\APICommands;

use Astro\API\Models\Site;
use Astro\API\Models\Contracts\APICommand;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Auth\Authenticatable;
use Gate;

class ListSites implements APICommand
{

    /**
     * Carry out the command, based on the provided $input.
     * @param array $input The input options as key=>value pairs.
     * @return mixed
     */
    public function execute($input, Authenticatable $user)
    {
        $sites = null;
        if($user->isAdmin()){
			$sites = Site::get();
		}else {
			$sites = Site::whereIn('id', $user->roles->pluck('site_id'))->get();
		}
        return $sites;
    }

    /**
     * Get the error messages for this command.
     * @param Collection $data The input data for this command.
     * @return array Custom error messages mapping field_name => message
     */
    public function messages(Collection $data, Authenticatable $user)
    {
        return [];
    }

    /**
     * Get the validation rules for this command.
     * @param Collection $data The input data for this command.
     * @return array The validation rules for this command.
     */
    public function rules(Collection $data, Authenticatable $user)
    {
        return [];
    }
}