using System.Diagnostics;
using System.Text.Json;
using System.Text.RegularExpressions;
using Microsoft.AspNetCore.Mvc;
using Moodify.Models;
using OpenAI.Chat;
using SpotifyAPI.Web;

namespace Moodify.Controllers;

public class HomeController(ILogger<HomeController> logger, IConfiguration configuration) : BaseController
{
    private readonly ILogger<HomeController> _logger = logger;

    public IActionResult Index()
    {
        return View();
    }

    [HttpPost]
    public IActionResult GetCategories(string request)
    {
        var apikey = configuration.GetSection("Api:SecretKey").Value;

        ChatClient client = new(model: "gpt-4o-mini", apikey);

        var formattedRequest =
            $"Give me in english one time of the day, one mood, one relationship and one musical taste which are related to : \\\"{request}\\\" separated by comma";

        ChatCompletion completion = client.CompleteChat(formattedRequest);

        ViewBag.Completion = completion;

        return View("Index");
    }

    private static string ExtractJson(string? input)
    {
        var match = Regex.Match(input, @"\{.*\}", RegexOptions.Singleline);
        
        return match.Success ? match.Value : string.Empty;
    }
    
    private static List<string> GetTrackNamesFromJson(string json)
    {
        
        var trackNames = new List<string>();
        var jsonDocument = JsonDocument.Parse(json);
        var tracks = jsonDocument.RootElement.GetProperty("tracks").EnumerateArray();

        foreach (var track in tracks)
        {
            trackNames.Add(track.GetProperty("title").GetString() ?? string.Empty);
        }

        return trackNames;
    }
    
    private List<string> GetTracksName(string completions)
    {
        var apikey = configuration.GetSection("Api:SecretKey").Value;

        ChatClient client = new(model: "gpt-4o-mini", apikey);
        
        var formattedRequest =
            $" return me a json object with a list of 30 song names related to these categories: \\\"{completions}\\\".  json must have this structure {{\"tracks\": [{{\"title\" : \"value\"}}]}}";
        
        ChatCompletion result = client.CompleteChat(formattedRequest);

        var content = result.Content[0].Text;

        
        var json = ExtractJson(content);
        
        var trackNames = GetTrackNamesFromJson(json);

        return trackNames;
    }

    [HttpGet]
    public async Task<JsonResult> CreatePlaylist(string completions)
    {
        var spotifyClient = new SpotifyClient("");

        if (!TempData.TryGetValue("access_token", out var value) ||
            value is not string) //if there is no access_token user need to log in
        {
            TempData["error"] = "Please log in";
            return new JsonResult("error");
        }

        if (IsTokenExpired() && !TempData.TryGetValue("refresh_token", out var refreshToken) &&
            refreshToken is string token) //refresh token if expire
        {
            var refreshedToken = await RefreshToken(token);
            spotifyClient = new SpotifyClient(refreshedToken.AccessToken);
        }

        var accessToken = value as string;

        var config = DefaultConfig.WithToken(accessToken!);
        spotifyClient = new SpotifyClient(config);

        try
        {
            var user = await spotifyClient.UserProfile.Current();
            var playlist =
                await spotifyClient.Playlists.Create(user.Id, new PlaylistCreateRequest("Moodify - playlist"));

            var tracksName = GetTracksName(completions);
            var trackIds = new List<string>();

            foreach (var trackName in tracksName)
            {
                var searchRequest = new SearchRequest(SearchRequest.Types.Track, trackName);
                var searchResponse = await spotifyClient.Search.Item(searchRequest);
                var trackId = searchResponse.Tracks.Items?.FirstOrDefault()?.Id;

                if (trackId != null)
                {
                    trackIds.Add(trackId);
                }
            }


            if (trackIds.Count != 0)
            {
                await spotifyClient.Playlists.AddItems(playlist.Id, new PlaylistAddItemsRequest(trackIds));
            }
            

            return new JsonResult("Success");
        }
        catch (Exception e)
        {
            TempData["error"] = "An error occured please try again";
        }


        return new JsonResult("error");
    }

    public RedirectResult Login()
    {
        var (verifier, challenge) = PKCEUtil.GenerateCodes();

        TempData["verifier"] = verifier;

        var loginRequest = new LoginRequest(
            new Uri("https://localhost:7206/home/callback"),
            "f4cd70cc16604aaf99eae2801a16a949",
            LoginRequest.ResponseType.Code
        )
        {
            CodeChallengeMethod = "S256",
            CodeChallenge = challenge,
            Scope =
            [
                Scopes.PlaylistReadPrivate, Scopes.PlaylistReadCollaborative, Scopes.PlaylistModifyPublic,
                Scopes.PlaylistModifyPrivate
            ]
        };

        var uri = loginRequest
            .ToUri(); // Redirect user to uri via your favorite web-server or open a local browser window 

        return Redirect(uri.ToString());
    }

    public async Task<RedirectToActionResult> Callback(string code)
    {
        if (!TempData.TryGetValue("verifier", out var tryGetVerifier) ||
            tryGetVerifier is not string verifier) //no verifier error handling
        {
            TempData["error"] = "An error occured, please try again";
            return RedirectToAction("Index", "Home");
        }


        try
        {
            // Note that we use the verifier calculated above!
            var initialResponse = await new OAuthClient().RequestToken(
                new PKCETokenRequest("f4cd70cc16604aaf99eae2801a16a949", code,
                    new Uri("https://localhost:7206/home/callback"), verifier)
            );

            //setting variable for utilities methods
            TempData["access_token"] = initialResponse.AccessToken;
            TempData["access_token_creation_date"] = initialResponse.CreatedAt;
            TempData["refresh_token"] = initialResponse.RefreshToken;
        }
        catch (Exception e)
        {
            TempData["error"] = e.Message;
        }

        return
            RedirectToAction("Index", "Home"); //once user auth is successful 
    }

    private bool IsTokenExpired()
    {
        if (!TempData.TryGetValue("access_token_creation_date", out var tokenCreationDate) ||
            tokenCreationDate is not DateTime)
            return true;

        return (DateTime.Now - (DateTime)tokenCreationDate).TotalHours > 1;
    }

    private async Task<PKCETokenResponse> RefreshToken(string refreshToken)
    {
        var newResponse = await new OAuthClient().RequestToken(
            new PKCETokenRefreshRequest("f4cd70cc16604aaf99eae2801a16a949", refreshToken)
        );

        return newResponse;
    }


    public IActionResult Privacy()
    {
        return View();
    }

    [ResponseCache(Duration = 0, Location = ResponseCacheLocation.None, NoStore = true)]
    public IActionResult Error()
    {
        return View(new ErrorViewModel { RequestId = Activity.Current?.Id ?? HttpContext.TraceIdentifier });
    }
}