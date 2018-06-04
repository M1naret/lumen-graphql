<?php

namespace M1naret\GraphQL\Support;

use GraphQL\Type\Definition\ResolveInfo;

class Query extends Field
{
    /**
     * @var ResolveInfo
     */
    private $resolveInfo;

    /**
     * @return ResolveInfo
     */
    public function getResolveInfo()
    {
        return $this->resolveInfo;
    }

    /**
     * @param ResolveInfo $resolveInfo
     *
     * @return self
     */
    public function setResolveInfo(ResolveInfo $resolveInfo) : self
    {
        $this->resolveInfo = $resolveInfo;

        return $this;
    }
}
