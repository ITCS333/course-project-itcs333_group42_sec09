{ pkgs ? import <nixpkgs> {} }:
pkgs.mkShell {
  buildInputs = [
    (pkgs.php82.withExtensions (exts: with exts; [ pdo_mysql mysqli ]))
    pkgs.php82Packages.composer
    pkgs.mysql80
  ];
}
