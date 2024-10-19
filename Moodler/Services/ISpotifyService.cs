using SpotifyAPI.Web;

namespace Moodler.Services;

public interface ISpotifyService
{
    SpotifyClient GetClient(string token);
}