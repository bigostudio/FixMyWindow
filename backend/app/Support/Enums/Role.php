<?php

namespace App\Support\Enums;

enum Role: string
{
    case SuperAdmin        = 'super_admin';
    case OpsAdmin          = 'ops_admin';
    case ProjectManager    = 'project_manager';
    case Surveyor          = 'surveyor';
    case Installer         = 'installer';
    case QcEngineer        = 'qc_engineer';
    case Accounts          = 'accounts';
    case BuilderFabricator = 'builder_fabricator';
}
