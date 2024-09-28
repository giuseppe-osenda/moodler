using Microsoft.AspNetCore.Mvc;
using SpotifyAPI.Web;

namespace Moodify.Controllers;

public class BaseController : Controller
{
    public static readonly SpotifyClientConfig DefaultConfig = SpotifyClientConfig.CreateDefault();

}