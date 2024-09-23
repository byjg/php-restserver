<?php

namespace ByJG\RestServer;

enum SerializationRuleEnum
{
    case Automatic;
    case SingleObject;
    case ObjectList;
    case Raw;
}
