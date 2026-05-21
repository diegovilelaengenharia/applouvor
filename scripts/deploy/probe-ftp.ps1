$ftpServer = "ftp://147.93.64.217/"
$ftpUsername = "u884436813.Diego"
$ftpPassword = "0w;G7y#lXe:MtC&k"

try {
    $request = [System.Net.WebRequest]::Create($ftpServer)
    $request.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectoryDetails
    $request.Credentials = New-Object System.Net.NetworkCredential($ftpUsername, $ftpPassword)
    
    $response = $request.GetResponse()
    $reader = New-Object System.IO.StreamReader($response.GetResponseStream())
    $listing = $reader.ReadToEnd()
    
    Write-Host "Directory Listing:"
    Write-Host $listing
    
    $reader.Close()
    $response.Close()
} catch {
    Write-Host "Error: $_"
}
