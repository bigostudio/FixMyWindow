<?php

namespace App\Support\Enums;

enum Role: string
{
    case Admin              = 'ops_admin';
    case OperationsManager  = 'ops_manager';
    case Supervisor         = 'supervisor';
    case Technician         = 'technician';
    case Customer           = 'customer';   // separate customers table
}
