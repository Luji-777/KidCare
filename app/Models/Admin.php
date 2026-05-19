namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable; // انتبهي لهذا السطر
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
use HasApiTokens, Notifiable;

protected $fillable = [
'name',
'email',
'password',
];

protected $hidden = [
'password',
];
}