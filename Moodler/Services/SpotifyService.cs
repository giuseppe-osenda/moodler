using System.ClientModel.Primitives;
using System.Net;
using Moodler.Helpers;
using OpenAI;
using SpotifyAPI.Web;

namespace Moodler.Services;

public class SpotifyService(ProxyHelper proxyHelper, IWebHostEnvironment env) : ISpotifyService
{
    public SpotifyClient GetClient(string token)
    {
        
        var spotifyConfig = SpotifyClientConfig.CreateDefault().WithToken(token);
        return new SpotifyClient(spotifyConfig);
    }
}