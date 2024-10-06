using Microsoft.AspNetCore.Mvc;
using SpotifyAPI.Web;

namespace Moodler.Controllers;

public class BaseController : Controller
{
    public static readonly SpotifyClientConfig DefaultConfig = SpotifyClientConfig.CreateDefault();

}