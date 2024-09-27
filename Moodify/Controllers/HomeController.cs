using System.Diagnostics;
using Microsoft.AspNetCore.Mvc;
using Moodify.Models;
using OpenAI.Chat;

namespace Moodify.Controllers;

public class HomeController(ILogger<HomeController> logger, IConfiguration configuration) : Controller
{
    private readonly ILogger<HomeController> _logger = logger;

    public IActionResult Index()
    {
        
        return View();
    }
    
    [HttpPost]
    public IActionResult SendRequest(string request)
    {
        var apikey = configuration.GetSection("Api:SecretKey").Value;
        
        ChatClient client = new(model: "gpt-4o-mini", apikey);

        var formattedRequest = $"Give me in english one time of the day, one mood, one relationship and one musical taste which are related to : \\\"{request}\\\" separated by comma";
            
        ChatCompletion completion = client.CompleteChat(formattedRequest);

        ViewBag.Completion = completion;

        return View("Index");
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